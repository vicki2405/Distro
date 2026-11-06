<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOutRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StoreSetting;
use App\Services\StockReportExportService;
use App\Services\StockService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockOutController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function create(Request $request): Response
    {
        $user = $request->user();
        abort_unless($user && ($user->isOwner() || $user->isStaff() || $user->can('stock.out')), 403, 'Akses ditolak. Anda tidak memiliki izin untuk mencatat pengeluaran barang.');

        $products = Product::with(['category', 'variants' => function ($q) {
            $q->orderBy('id');
        }])->orderBy('name')->get();

        $query = StockMovement::with(['variant.product.category', 'user'])
            ->where('type', 'out')
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
        $storeSetting = StoreSetting::current();

        return Inertia::render('Warehouse/StockOut', [
            'products' => $products,
            'movements' => $movements,
            'filters' => $request->only(['search']),
            'storeSetting' => $storeSetting,
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

        $filtered = $exportService->getFilteredMovements($request, 'out');
        $format = (string) $request->input('format', 'pdf');

        if ($format === 'excel') {
            return $exportService->exportExcel(
                $filtered['movements'],
                'out',
                $filtered['periodTitle'],
                $filtered['filenameBase']
            );
        }

        return $exportService->exportPdf(
            $filtered['movements'],
            'out',
            $filtered['periodTitle'],
            $filtered['filenameBase']
        );
    }

    public function store(StockOutRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('proofs', 'public');
        }

        $recordedCount = 0;
        $recipient = $validated['recipient_name'] ?? null;
        $destinationDesc = $validated['destination'].($recipient ? " ({$recipient})" : '');

        foreach ($validated['items'] as $item) {
            $qty = (int) $item['quantity'];
            if ($qty <= 0) {
                continue;
            }

            $variant = ProductVariant::where('id', $item['variant_id'])
                ->where('product_id', $validated['product_id'])
                ->first();

            if (! $variant) {
                return back()->withErrors([
                    'error' => 'Varian ukuran yang dipilih tidak sesuai dengan artikel produk yang dipilih.',
                ]);
            }

            if ($variant->current_stock < $qty) {
                return back()->withErrors([
                    'error' => "Stok ukuran {$variant->size} tidak mencukupi (Tersedia: {$variant->current_stock}, diminta: {$qty}).",
                ]);
            }

            try {
                $this->stockService->recordMovement(
                    variant: $variant,
                    user: $user,
                    type: 'out',
                    qty: $qty,
                    reference: $validated['reference_number'],
                    destination: $destinationDesc,
                    proofImage: $proofPath,
                    notes: $validated['notes'] ?? null
                );
                $recordedCount++;
            } catch (Exception $e) {
                return back()->withErrors(['error' => $e->getMessage()]);
            }
        }

        if ($recordedCount === 0) {
            return back()->withErrors(['error' => 'Harap isi jumlah pengeluaran minimal 1 pcs.']);
        }

        return redirect()->route('warehouse.dashboard')
            ->with('success', 'Pengeluaran barang berhasil dicatat.');
    }

    public function destroy(Request $request, StockMovement $movement): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && $user->isOwner(), 403, 'Akses ditolak. Fitur hapus riwayat stok hanya dapat dilakukan oleh akun Admin / Owner.');

        if ($movement->type !== 'out') {
            abort(404);
        }

        try {
            $ref = $movement->reference_number ?: 'Tanpa No. SJ';
            $productName = $movement->variant?->product?->name ?? 'Produk';
            $size = $movement->variant?->size ?? '';
            $qty = $movement->quantity;

            $this->stockService->revertAndDestroyMovement($movement);

            return back()->with('success', "Transaksi pengeluaran [{$ref}] untuk {$productName} (Size {$size}) berhasil dihapus dan stok produk telah dikembalikan sebanyak {$qty} pcs.");
        } catch (Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
