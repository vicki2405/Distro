<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\WarehouseDashboardController;
use App\Models\Product;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('warehouse.dashboard');
    }

    $featuredProducts = Product::with(['category', 'variants' => function ($q) {
        $q->orderBy('id');
    }])->latest()->take(3)->get();

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'featuredProducts' => $featuredProducts,
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('warehouse.dashboard');
    })->name('dashboard');

    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseDashboardController::class, 'index'])->name('dashboard');
        Route::resource('products', ProductController::class);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::get('/in', [StockInController::class, 'create'])->name('in.create');
        Route::post('/in', [StockInController::class, 'store'])->name('in.store');
        Route::get('/out', [StockOutController::class, 'create'])->name('out.create');
        Route::post('/out', [StockOutController::class, 'store'])->name('out.store');
        Route::get('/movements', [StockMovementController::class, 'index'])->name('movements');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
