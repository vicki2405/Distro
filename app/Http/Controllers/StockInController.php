<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\StockReportExportService;
use App\Services\StockService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockInController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && ($user->isOwner() || $user->isStaff() || $user->can('stock.in')), 403, 'Akses ditolak. Anda tidak memiliki izin untuk input barang masuk.');

        $products = Product::with(['category', 'images', 'variants' => function ($q) {
            $q->orderBy('id');
        }])->orderBy('name')->get();

        $categories = Category::orderBy('name')->get();

        $query = StockMovement::with(['variant.product.category', 'variant.product.images', 'user'])
            ->where('type', 'in')
            ->latest();

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%")
                    ->orWhereHas('variant', function ($sub) use ($search) {
                        $sub->where('sku', 'like', "%{$search}%")
                            ->orWhereHas('product', function ($p) use ($search) {
                                $p->where('name', 'like', "%{$search}%")
                                    ->orWhere('code', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $movements = $query->paginate(15)->withQueryString();

        return Inertia::render('Warehouse/StockIn', [
            'products' => $products,
            'categories' => $categories,
            'movements' => $movements,
            'filters' => $request->only(['search']),
        ]);
    }

    public function export(Request $request, StockReportExportService $exportService)
    {
        $user = $request->user();
        abort_unless(
            $user && ($user->can('stock.report') || $user->isOwner()),
            403,
            'Akses ditolak. Anda tidak memiliki izin untuk mencetak laporan stok.'
        );

        $filtered = $exportService->getFilteredMovements($request, 'in');
        $format = (string) $request->input('format', 'pdf');

        if ($format === 'excel') {
            return $exportService->exportExcel(
                $filtered['movements'],
                'in',
                $filtered['periodTitle'],
                $filtered['filenameBase']
            );
        }

        return $exportService->exportPdf(
            $filtered['movements'],
            'in',
            $filtered['periodTitle'],
            $filtered['filenameBase']
        );
    }

    public function store(StockInRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $product = Product::with('variants')->findOrFail($validated['product_id']);

        // Simpan bukti foto jika diunggah
        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('proofs', 'public');
        }

        $recordedCount = 0;

        foreach ($validated['items'] as $item) {
            $qty = (int) $item['quantity'];
            if ($qty <= 0) {
                continue;
            }

            $size = strtoupper(trim($item['size']));
            $sku = $product->code.'-'.$size;

            $variant = ProductVariant::firstOrCreate(
                ['sku' => $sku],
                [
                    'product_id' => $product->id,
                    'size' => $size,
                    'current_stock' => 0,
                ]
            );

            $supplier = $validated['supplier_name'] ?? null;
            $destination = 'Penerimaan Konveksi'.($supplier ? " ({$supplier})" : '');

            try {
                $this->stockService->recordMovement(
                    variant: $variant,
                    user: $user,
                    type: 'in',
                    qty: $qty,
                    reference: $validated['reference_number'],
                    destination: $destination,
                    proofImage: $proofPath,
                    notes: $validated['notes'] ?? null
                );
                $recordedCount++;
            } catch (Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        if ($recordedCount === 0) {
            return back()->withErrors(['error' => 'Harap isi jumlah minimal 1 pcs pada salah satu ukuran.']);
        }

        return redirect()->route('warehouse.dashboard')
            ->with('success', "Penerimaan restock '{$product->name}' berhasil dicatat ke stok gudang.");
    }

    public function destroy(Request $request, StockMovement $movement): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isOwner(), 403, 'Akses ditolak. Fitur hapus riwayat stok hanya dapat dilakukan oleh akun Admin / Owner.');

        if ($movement->type !== 'in') {
            abort(404);
        }

        try {
            $ref = $movement->reference_number ?: 'Tanpa No. SJ';
            $productName = $movement->variant?->product?->name ?? 'Produk';
            $size = $movement->variant?->size ?? '';
            $qty = $movement->quantity;

            $this->stockService->revertAndDestroyMovement($movement);

            return back()->with('success', "Transaksi penerimaan [{$ref}] untuk {$productName} (Size {$size}) berhasil dihapus dan stok produk telah dikurangi kembali sebanyak {$qty} pcs.");
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
