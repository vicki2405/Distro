<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class StockService
{
    /**
     * Catat mutasi pergerakan barang (In / Out / Adjustment) secara atomik.
     *
     * @throws Exception
     */
    public function recordMovement(
        ProductVariant $variant,
        User $user,
        string $type,
        int $qty,
        ?string $reference = null,
        ?string $destination = null,
        ?string $proofImage = null,
        ?string $notes = null
    ): StockMovement {
        if ($qty <= 0) {
            throw new Exception('Jumlah mutasi harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($variant, $user, $type, $qty, $reference, $destination, $proofImage, $notes) {
            // Lock row untuk mencegah race condition
            $lockedVariant = ProductVariant::where('id', $variant->id)->lockForUpdate()->firstOrFail();
            $before = $lockedVariant->current_stock;

            if ($type === 'in') {
                $after = $before + $qty;
            } elseif ($type === 'out') {
                $after = $before - $qty;
                if ($after < 0) {
                    throw new Exception("Stok untuk SKU {$lockedVariant->sku} tidak mencukupi (Sisa: {$before}, diminta: {$qty}).");
                }
            } elseif ($type === 'adjustment') {
                // Adjustment langsung mengganti atau menambah/mengurang
                $after = $before + $qty;
            } else {
                throw new Exception("Tipe mutasi '{$type}' tidak valid.");
            }

            $lockedVariant->update(['current_stock' => $after]);

            return StockMovement::create([
                'product_variant_id' => $lockedVariant->id,
                'user_id' => $user->id,
                'type' => $type,
                'quantity' => $qty,
                'stock_before' => $before,
                'stock_after' => $after,
                'reference_number' => $reference,
                'destination' => $destination,
                'proof_image' => $proofImage,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Catat hasil stock opname fisik dan hitung selisih.
     */
    public function recordOpname(
        ProductVariant $variant,
        User $user,
        int $physicalStock,
        ?string $notes = null,
        bool $adjustStock = false
    ): StockOpname {
        return DB::transaction(function () use ($variant, $user, $physicalStock, $notes, $adjustStock) {
            $lockedVariant = ProductVariant::where('id', $variant->id)->lockForUpdate()->firstOrFail();
            $systemStock = $lockedVariant->current_stock;
            $difference = $physicalStock - $systemStock;

            $opname = StockOpname::create([
                'user_id' => $user->id,
                'product_variant_id' => $lockedVariant->id,
                'system_stock' => $systemStock,
                'physical_stock' => $physicalStock,
                'difference' => $difference,
                'notes' => $notes,
            ]);

            if ($adjustStock && $difference !== 0) {
                $lockedVariant->update(['current_stock' => $physicalStock]);

                StockMovement::create([
                    'product_variant_id' => $lockedVariant->id,
                    'user_id' => $user->id,
                    'type' => 'adjustment',
                    'quantity' => abs($difference),
                    'stock_before' => $systemStock,
                    'stock_after' => $physicalStock,
                    'reference_number' => 'OPNAME-'.$opname->id,
                    'destination' => 'Penyesuaian Opname',
                    'notes' => "Hasil audit opname fisik: {$physicalStock} pcs (Selisih: {$difference} pcs).",
                ]);
            }

            return $opname;
        });
    }

    /**
     * Eksekusi penyesuaian stok sistem agar sinkron dengan hasil hitung fisik opname.
     * Khusus dilakukan oleh Admin/Owner.
     */
    public function adjustOpnameStock(StockOpname $opname, User $user): void
    {
        if (StockMovement::where('reference_number', 'OPNAME-ADJ-'.$opname->id)->exists()) {
            throw new Exception('Hasil audit stock opname ini sudah pernah disinkronkan ke stok sistem sebelumnya.');
        }

        DB::transaction(function () use ($opname, $user) {
            $variant = ProductVariant::where('id', $opname->product_variant_id)
                ->lockForUpdate()
                ->firstOrFail();

            $difference = $opname->difference;
            if ($difference !== 0) {
                $stockBefore = $variant->current_stock;
                $newStock = max(0, $opname->physical_stock);

                $variant->update(['current_stock' => $newStock]);

                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'user_id' => $user->id,
                    'type' => 'adjustment',
                    'quantity' => abs($difference),
                    'stock_before' => $stockBefore,
                    'stock_after' => $newStock,
                    'reference_number' => 'OPNAME-ADJ-'.$opname->id,
                    'destination' => 'Penyesuaian Audit Opname',
                    'notes' => "Sinkronisasi stok fisik oleh Owner ({$newStock} pcs, selisih audit: {$difference} pcs).",
                ]);
            }
        });
    }

    /**
     * Hapus mutasi pergerakan barang dan sesuaikan (revert) stok produk secara atomik.
     * Khusus dilakukan oleh Admin/Owner.
     *
     * @throws Exception
     */
    public function revertAndDestroyMovement(StockMovement $movement): void
    {
        DB::transaction(function () use ($movement) {
            $variant = ProductVariant::where('id', $movement->product_variant_id)
                ->lockForUpdate()
                ->first();

            if ($variant) {
                if ($movement->type === 'in') {
                    // Penerimaan dibatalkan: kurangi kembali stok produk
                    if ($variant->current_stock < $movement->quantity) {
                        throw new Exception("Tidak dapat membatalkan transaksi ini karena sisa stok saat ini ({$variant->current_stock} pcs) lebih kecil dari jumlah transaksi ({$movement->quantity} pcs). Stok kemungkinan sudah terpakai/terjual.");
                    }
                    $variant->decrement('current_stock', $movement->quantity);
                } elseif ($movement->type === 'out') {
                    // Pengeluaran dibatalkan: kembalikan kembali stok ke produk
                    $variant->increment('current_stock', $movement->quantity);
                }
            }

            // Hapus file bukti foto fisik hanya jika tidak digunakan oleh mutasi varian lain dalam surat jalan yang sama
            if ($movement->proof_image && Storage::disk('public')->exists($movement->proof_image)) {
                $isSharedWithOtherMovement = StockMovement::where('proof_image', $movement->proof_image)
                    ->where('id', '!=', $movement->id)
                    ->exists();

                if (! $isSharedWithOtherMovement) {
                    Storage::disk('public')->delete($movement->proof_image);
                }
            }

            $movement->delete();
        });
    }
}
