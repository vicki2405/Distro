<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
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

        return Inertia::render('Warehouse/Products/Create', [
            'categories' => $categories,
            'defaultSizes' => ['S', 'M', 'L', 'XL', 'XXL'],
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

    public function edit(Product $product): Response
    {
        $product->load(['category', 'variants', 'images']);
        $categories = Category::orderBy('name')->get();

        return Inertia::render('Warehouse/Products/Edit', [
            'product' => $product,
            'categories' => $categories,
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

                if (! $product->image) {
                    $product->update(['image' => $path]);
                }
            }
        }

        // Tambah varian ukuran baru jika ada
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
