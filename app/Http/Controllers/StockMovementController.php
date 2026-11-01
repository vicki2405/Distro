<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMovementController extends Controller
{
    public function index(Request $request): Response
    {
        $query = StockMovement::with(['variant.product.category', 'user'])->latest();

        if ($request->filled('type') && in_array($request->input('type'), ['in', 'out', 'adjustment'])) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
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

        return Inertia::render('Warehouse/Movements', [
            'movements' => $movements,
            'filters' => $request->only(['type', 'search']),
        ]);
    }
}
