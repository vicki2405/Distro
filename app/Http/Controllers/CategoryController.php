<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        $categories = Category::withCount('products')->orderBy('name')->get();

        return Inertia::render('Warehouse/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isOwner() || $user->can('categories.manage')), 403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola kategori.');

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
        ], [
            'name.required' => 'Nama kategori produk wajib diisi.',
            'name.unique' => 'Kategori produk ini sudah terdaftar.',
        ]);

        Category::create([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('warehouse.categories.index')
            ->with('success', "Kategori produk '{$validated['name']}' berhasil ditambahkan.");
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isOwner() || $user->can('categories.manage')), 403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola kategori.');

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name,'.$category->id,
        ], [
            'name.required' => 'Nama kategori produk wajib diisi.',
            'name.unique' => 'Nama kategori produk sudah digunakan.',
        ]);

        $category->update([
            'name' => trim($validated['name']),
            'slug' => Str::slug($validated['name']),
        ]);

        return redirect()->route('warehouse.categories.index')
            ->with('success', 'Nama kategori produk berhasil diperbarui.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user && ($user->isOwner() || $user->can('categories.manage')), 403, 'Akses ditolak. Anda tidak memiliki izin untuk mengelola kategori.');

        if ($category->products()->exists()) {
            return back()->withErrors([
                'error' => "Kategori '{$category->name}' tidak dapat dihapus karena masih digunakan oleh produk di katalog.",
            ]);
        }

        $category->delete();

        return redirect()->route('warehouse.categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil dihapus.");
    }
}
