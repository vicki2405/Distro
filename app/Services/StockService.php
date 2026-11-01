<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StockOpname;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

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
}
