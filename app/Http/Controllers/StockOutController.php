<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockOutRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\StoreSetting;
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
