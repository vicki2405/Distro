<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockInRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\StockService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StockInController extends Controller
{
    public function __construct(
        protected StockService $stockService
    ) {}

    public function create(): Response
    {
        $products = Product::with(['category', 'variants' => function ($q) {
            $q->orderBy('id');
        }])->orderBy('name')->get();

        $categories = Category::orderBy('name')->get();

        return Inertia::render('Warehouse/StockIn', [
            'products' => $products,
            'categories' => $categories,
        ]);
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
}
