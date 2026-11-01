<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOutRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockOutController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function create(): Response
    {
        $products = Product::with(['category', 'variants' => function ($q) {
            $q->orderBy('id');
        }])->orderBy('name')->get();

        return Inertia::render('Warehouse/StockOut', [
            'products' => $products,
            'destinations' => [
                'Kirim ke Toko Distro',
                'Kirim ke Reseller / Grosir',
                'Pesanan Online (Shopee/TikTok/Tokopedia)',
                'Pesanan WhatsApp / Langsung',
                'Retur ke Konveksi / Cacat',
                'Keperluan Display / Endorsement',
            ],
        ]);
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

            $variant = ProductVariant::findOrFail($item['variant_id']);

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
}
