<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Models\Size;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SizeController extends Controller
{
    public function index(): Response
    {
        $sizes = Size::withCount('variants')->orderBy('order')->orderBy('name')->get();

        return Inertia::render('Warehouse/Sizes/Index', [
            'sizes' => $sizes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:sizes,name',
            'order' => 'nullable|integer',
        ], [
            'name.required' => 'Nama ukuran wajib diisi.',
            'name.unique' => 'Ukuran ini sudah terdaftar di master data.',
        ]);

        $name = strtoupper(trim($validated['name']));
        $order = isset($validated['order']) ? (int) $validated['order'] : ((Size::max('order') ?? 0) + 1);

        Size::create([
            'name' => $name,
            'order' => $order,
        ]);

        return redirect()->route('warehouse.sizes.index')
            ->with('success', "Ukuran '{$name}' berhasil ditambahkan ke Master Data.");
    }

    public function update(Request $request, Size $size): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:20|unique:sizes,name,'.$size->id,
            'order' => 'nullable|integer',
        ], [
            'name.required' => 'Nama ukuran wajib diisi.',
            'name.unique' => 'Nama ukuran sudah digunakan.',
        ]);

        $oldName = $size->name;
        $newName = strtoupper(trim($validated['name']));

        $size->update([
            'name' => $newName,
            'order' => isset($validated['order']) ? (int) $validated['order'] : $size->order,
        ]);

        if ($oldName !== $newName) {
            ProductVariant::where('size', $oldName)->update(['size' => $newName]);
        }

        return redirect()->route('warehouse.sizes.index')
            ->with('success', "Ukuran '{$newName}' berhasil diperbarui.");
    }

    public function destroy(Size $size): RedirectResponse
    {
        if ($size->variants()->exists()) {
            return back()->withErrors([
                'error' => "Ukuran '{$size->name}' tidak dapat dihapus karena masih digunakan oleh varian produk di gudang.",
            ]);
        }

        $name = $size->name;
        $size->delete();

        return redirect()->route('warehouse.sizes.index')
            ->with('success', "Ukuran '{$name}' berhasil dihapus dari Master Data.");
    }
}
