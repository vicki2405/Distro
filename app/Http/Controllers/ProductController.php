<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Size;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isOwner = $user->isOwner();

        $query = Product::with(['category', 'variants' => function ($q) {
            $q->orderBy('id');
        }]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        $products = $query->latest()->paginate(10)->withQueryString();
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Warehouse/Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category']),
            'isOwner' => $isOwner,
        ]);
    }

    public function create(): Response
    {
        $categories = Category::orderBy('name')->get();
        $availableSizes = Size::orderBy('order')->orderBy('name')->pluck('name');

        return Inertia::render('Warehouse/Products/Create', [
            'categories' => $categories,
            'defaultSizes' => ['S', 'M', 'L', 'XL', 'XXL'],
            'availableSizes' => $availableSizes,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Generate kode internal unik secara otomatis di balik layar tanpa perlu diinput user
        do {
            $internalCode = 'PRD-'.strtoupper(Str::random(6));
        } while (Product::where('code', $internalCode)->exists());

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'category_id' => $validated['category_id'],
            'code' => $internalCode,
            'name' => trim($validated['name']),
            'cost_price' => $validated['cost_price'] ?? 0,
            'selling_price' => $validated['selling_price'],
            'description' => $validated['description'] ?? null,
            'image' => $imagePath,
        ]);

        // Simpan galeri multi-foto jika diupload (Depan, Belakang, Kiri, Kanan, dll)
        if ($request->hasFile('images')) {
            $labels = $request->input('image_labels', []);
            foreach ($request->file('images') as $idx => $imgFile) {
                $path = $imgFile->store('products', 'public');
                $label = $labels[$idx] ?? null;
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'label' => $label,
                    'order' => $idx,
                ]);

                if (! $product->image && $idx === 0) {
                    $product->update(['image' => $path]);
                }
            }
        }

        // Inisialisasi varian ukuran ke database dengan stok awal 0
        foreach ($validated['sizes'] as $size) {
            $cleanSize = strtoupper(trim($size));
            Size::firstOrCreate(
                ['name' => $cleanSize],
                ['order' => (Size::max('order') ?? 0) + 1]
            );
            ProductVariant::create([
                'product_id' => $product->id,
                'size' => $cleanSize,
                'sku' => $product->code.'-'.$cleanSize,
                'current_stock' => 0,
            ]);
        }

        return redirect()->route('warehouse.products.index')
            ->with('success', "Artikel '{$product->name}' berhasil ditambahkan ke Master Data.");
    }

    public function show(Product $product, Request $request): Response
    {
        $user = $request->user();
        $isOwner = $user->isOwner();

        $product->load([
            'category',
            'images',
            'variants' => function ($q) {
                $q->orderBy('id');
            },
        ]);

        // Muat riwayat mutasi stok untuk produk ini
        $variantIds = $product->variants->pluck('id');
        $movements = StockMovement::with(['variant', 'user'])
            ->whereIn('product_variant_id', $variantIds)
            ->latest()
            ->take(8)
            ->get();

        $totalStock = (int) $product->variants->sum('current_stock');

        return Inertia::render('Warehouse/Products/Show', [
            'product' => $product,
            'movements' => $movements,
            'totalStock' => $totalStock,
            'isOwner' => $isOwner,
        ]);
    }

    public function edit(Product $product, Request $request): Response
    {
        $user = $request->user();
        $isOwner = $user ? $user->isOwner() : false;

        $product->load([
            'category',
            'variants' => fn ($q) => $q->orderBy('id'),
            'images',
        ]);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Warehouse/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
            'availableSizes' => Size::orderBy('order')->orderBy('name')->pluck('name'),
            'isOwner' => $isOwner,
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        $updateData = [
            'category_id' => $validated['category_id'],
            'name' => trim($validated['name']),
            'cost_price' => $validated['cost_price'] ?? 0,
            'selling_price' => $validated['selling_price'],
            'description' => $validated['description'] ?? null,
        ];

        if ($request->boolean('delete_main_image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $updateData['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $updateData['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($updateData);

        // Hapus foto galeri yang ditandai untuk dihapus
        if (! empty($validated['deleted_image_ids'])) {
            $deletedImages = ProductImage::where('product_id', $product->id)
                ->whereIn('id', $validated['deleted_image_ids'])
                ->get();

            foreach ($deletedImages as $delImg) {
                if ($product->image === $delImg->image_path) {
                    $product->image = null;
                }
                if (Storage::disk('public')->exists($delImg->image_path)) {
                    Storage::disk('public')->delete($delImg->image_path);
                }
                $delImg->delete();
            }
        }

        // Simpan foto galeri multi-foto baru jika diupload
        if ($request->hasFile('images')) {
            $existingCount = $product->images()->count();
            $labels = $request->input('image_labels', []);
            foreach ($request->file('images') as $idx => $imgFile) {
                $path = $imgFile->store('products', 'public');
                $label = $labels[$idx] ?? null;
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_path' => $path,
                    'label' => $label,
                    'order' => $existingCount + $idx,
                ]);
            }
        }

        // Pemilihan Foto Utama secara eksplisit jika disediakan
        if (! empty($validated['primary_image_id'])) {
            $chosenImage = $product->images()->where('id', $validated['primary_image_id'])->first();
            if ($chosenImage) {
                $product->image = $chosenImage->image_path;
            }
        }

        // Auto-sinkronisasi foto utama agar selalu valid dan tidak broken
        if (! $product->image || Storage::disk('public')->missing($product->image)) {
            $firstValidImage = $product->images()
                ->orderBy('order')
                ->orderBy('id')
                ->get()
                ->first(fn ($img) => Storage::disk('public')->exists($img->image_path));

            $product->image = $firstValidImage?->image_path;
        }

        $product->save();

        $user = $request->user();
        $isOwner = $user ? $user->isOwner() : false;

        // 1. Hapus varian jika diminta oleh Owner
        if ($isOwner && ! empty($validated['deleted_variant_ids'])) {
            $remainingCount = $product->variants()->whereNotIn('id', $validated['deleted_variant_ids'])->count()
                + count($validated['new_variants'] ?? [])
                + count($validated['new_sizes'] ?? []);

            if ($remainingCount < 1) {
                return back()->withErrors(['error' => 'Artikel wajib memiliki minimal 1 varian ukuran.']);
            }

            $product->variants()->whereIn('id', $validated['deleted_variant_ids'])->delete();
        }

        // 2. Koreksi stok varian langsung oleh Owner (Audit tercatat ke StockMovement)
        if ($isOwner && ! empty($validated['variant_stocks'])) {
            foreach ($validated['variant_stocks'] as $stockItem) {
                $variant = $product->variants()->where('id', $stockItem['id'])->first();
                if (! $variant) {
                    continue;
                }

                $newStock = (int) $stockItem['current_stock'];
                $oldStock = (int) $variant->current_stock;

                if ($newStock !== $oldStock) {
                    $delta = $newStock - $oldStock;

                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'user_id' => $user->id,
                        'type' => 'adjustment',
                        'quantity' => abs($delta),
                        'stock_before' => $oldStock,
                        'stock_after' => $newStock,
                        'reference_number' => 'ADJ-'.strtoupper(Str::random(6)),
                        'destination' => 'Koreksi Stok Owner',
                        'notes' => 'Koreksi stok langsung oleh Owner pada artikel '.$product->name.' (Size '.$variant->size.')',
                    ]);

                    $variant->update(['current_stock' => $newStock]);
                }
            }
        }

        // 3. Tambah varian ukuran baru dengan stok awal (new_variants)
        if (! empty($validated['new_variants'])) {
            foreach ($validated['new_variants'] as $newVar) {
                $cleanSize = strtoupper(trim($newVar['size']));
                $initialStock = isset($newVar['initial_stock']) ? max(0, (int) $newVar['initial_stock']) : 0;

                Size::firstOrCreate(
                    ['name' => $cleanSize],
                    ['order' => (Size::max('order') ?? 0) + 1]
                );

                $variant = ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'size' => $cleanSize,
                    ],
                    [
                        'sku' => $product->code.'-'.$cleanSize,
                        'current_stock' => $initialStock,
                    ]
                );

                if ($initialStock > 0 && $variant->wasRecentlyCreated && $user) {
                    StockMovement::create([
                        'product_variant_id' => $variant->id,
                        'user_id' => $user->id,
                        'type' => 'adjustment',
                        'quantity' => $initialStock,
                        'stock_before' => 0,
                        'stock_after' => $initialStock,
                        'reference_number' => 'ADJ-'.strtoupper(Str::random(6)),
                        'destination' => 'Saldo Awal Ukuran Baru',
                        'notes' => 'Pendaftaran ukuran baru Size '.$cleanSize.' dengan stok '.$initialStock.' pcs.',
                    ]);
                }
            }
        }

        // Tambah varian ukuran baru (new_sizes legacy)
        if (! empty($validated['new_sizes'])) {
            foreach ($validated['new_sizes'] as $newSize) {
                $cleanSize = strtoupper(trim($newSize));
                ProductVariant::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'size' => $cleanSize,
                    ],
                    [
                        'sku' => $product->code.'-'.$cleanSize,
                        'current_stock' => 0,
                    ]
                );
            }
        }

        return redirect()->route('warehouse.products.index')
            ->with('success', "Data artikel '{$product->name}' berhasil diperbarui.");
    }

    public function destroy(Product $product): RedirectResponse
    {
        // Cek apakah ada mutasi stok yang terhubung
        $hasMovements = $product->variants()->whereHas('stockMovements')->exists();

        if ($hasMovements) {
            return back()->withErrors([
                'error' => "Artikel '{$product->name}' tidak dapat dihapus karena sudah memiliki riwayat mutasi stok audit abadi.",
            ]);
        }

        foreach ($product->images as $img) {
            if (Storage::disk('public')->exists($img->image_path)) {
                Storage::disk('public')->delete($img->image_path);
            }
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('warehouse.products.index')
            ->with('success', "Artikel '{$product->name}' berhasil dihapus dari Master Data.");
    }
}
