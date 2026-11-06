<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SizeController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\StoreSettingController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function (Request $request) {
    $query = Product::with([
        'category',
        'variants' => fn ($q) => $q->orderBy('id'),
        'images',
    ]);

    if ($request->filled('search')) {
        $search = (string) $request->input('search');
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    if ($request->filled('category')) {
        $query->where('category_id', $request->input('category'));
    }

    if ($request->input('sort') === 'price_asc') {
        $query->orderBy('selling_price', 'asc');
    } elseif ($request->input('sort') === 'price_desc') {
        $query->orderBy('selling_price', 'desc');
    } elseif ($request->input('sort') === 'name') {
        $query->orderBy('name', 'asc');
    } else {
        $query->latest();
    }

    $products = $query->paginate(12)->withQueryString();
    $categories = Category::withCount('products')->orderBy('name')->get();

    $stats = [
        'total_products' => Product::count(),
        'total_pieces' => (int) ProductVariant::sum('current_stock'),
        'total_categories' => Category::count(),
    ];

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => false,
        'products' => $products,
        'categories' => $categories,
        'filters' => $request->only(['search', 'category', 'sort']),
        'stats' => $stats,
        'storeSetting' => StoreSetting::current(),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('warehouse.dashboard');
    })->name('dashboard');

    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseDashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('sizes', SizeController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/in', [StockInController::class, 'create'])->name('in.create');
        Route::get('/in/export', [StockInController::class, 'export'])->name('in.export');
        Route::post('/in', [StockInController::class, 'store'])->name('in.store');
        Route::delete('/in/{movement}', [StockInController::class, 'destroy'])->name('in.destroy');
        Route::get('/out', [StockOutController::class, 'create'])->name('out.create');
        Route::get('/out/export', [StockOutController::class, 'export'])->name('out.export');
        Route::post('/out', [StockOutController::class, 'store'])->name('out.store');
        Route::delete('/out/{movement}', [StockOutController::class, 'destroy'])->name('out.destroy');
        Route::get('/movements', fn () => redirect()->route('warehouse.opnames.index'))->name('movements');
        Route::get('/opnames', [StockOpnameController::class, 'index'])->name('opnames.index');
        Route::get('/opnames/sheet', [StockOpnameController::class, 'sheetView'])->name('opnames.sheet');
        Route::get('/opnames/sheet/download', [StockOpnameController::class, 'sheetDownload'])->name('opnames.sheet.download');
        Route::get('/opnames/create', [StockOpnameController::class, 'create'])->name('opnames.create');
        Route::post('/opnames', [StockOpnameController::class, 'store'])->name('opnames.store');
        Route::post('/opnames/{opname}/adjust', [StockOpnameController::class, 'adjust'])->name('opnames.adjust');
        Route::delete('/opnames/{opname}', [StockOpnameController::class, 'destroy'])->name('opnames.destroy');
        Route::get('/opnames/export', [StockOpnameController::class, 'export'])->name('opnames.export');
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('roles', RoleController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/settings', [StoreSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [StoreSettingController::class, 'update'])->name('settings.update');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
