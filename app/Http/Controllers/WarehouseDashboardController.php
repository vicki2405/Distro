<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $isOwner = $user->isOwner();

        // 1. Metrik Utama
        $totalPieces = (int) ProductVariant::sum('current_stock');
        $totalVariants = ProductVariant::count();
        $lowStockVariants = ProductVariant::with('product')
            ->where('current_stock', '<=', 5)
            ->get();

        $todayStart = now()->startOfDay();
        $todayIn = (int) StockMovement::where('type', 'in')->where('created_at', '>=', $todayStart)->sum('quantity');
        $todayOut = (int) StockMovement::where('type', 'out')->where('created_at', '>=', $todayStart)->sum('quantity');

        // Nilai aset (HPP hanya dihitung jika Owner)
        $totalCostValue = 0;
        $totalSellingValue = 0;

        $variants = ProductVariant::with('product')->get();
        foreach ($variants as $v) {
            $totalSellingValue += ($v->current_stock * ($v->product->selling_price ?? 0));
            if ($isOwner) {
                $totalCostValue += ($v->current_stock * ($v->product->cost_price ?? 0));
            }
        }

        // 2. Data Stok Baju per Artikel & Varian
        $query = Product::with(['category', 'variants' => function ($q) {
            $q->orderBy('id');
        }]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category_id', $request->input('category'));
        }

        $products = $query->latest()->paginate(10)->withQueryString();

        // 3. Mutasi Terkini (Activity Feed)
        $recentMovements = StockMovement::with(['variant.product', 'user'])
            ->latest()
            ->take(8)
            ->get();

        return Inertia::render('Warehouse/Dashboard', [
            'metrics' => [
                'total_pieces' => $totalPieces,
                'total_variants' => $totalVariants,
                'today_in' => $todayIn,
                'today_out' => $todayOut,
                'low_stock_count' => $lowStockVariants->count(),
                'total_selling_value' => $totalSellingValue,
                'total_cost_value' => $isOwner ? $totalCostValue : null,
                'is_owner' => $isOwner,
            ],
            'products' => $products,
            'recentMovements' => $recentMovements,
            'filters' => $request->only(['search', 'category']),
        ]);
    }
}
