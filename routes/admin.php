<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminProductRequestController;
use App\Http\Controllers\Admin\AdminSellerController;
use App\Http\Controllers\Admin\AdminShippingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
| Runs the catalog, onboards stores by hand, and owns shipping configuration.
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/sellers', [AdminSellerController::class, 'index'])->name('sellers.index');
        Route::get('/sellers/create', [AdminSellerController::class, 'create'])->name('sellers.create');
        Route::post('/sellers', [AdminSellerController::class, 'store'])->name('sellers.store');
        Route::get('/sellers/{seller}/edit', [AdminSellerController::class, 'edit'])->name('sellers.edit');
        Route::put('/sellers/{seller}', [AdminSellerController::class, 'update'])->name('sellers.update');

        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::post('/products/{product}/review', [AdminProductController::class, 'review'])->name('products.review');

        Route::get('/categories', [AdminCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [AdminCategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('categories.update');

        Route::get('/shipping', [AdminShippingController::class, 'index'])->name('shipping.index');
        Route::post('/shipping/zones', [AdminShippingController::class, 'storeZone'])->name('shipping.zones.store');
        Route::post('/shipping/providers', [AdminShippingController::class, 'storeProvider'])->name('shipping.providers.store');
        Route::post('/shipping/rates', [AdminShippingController::class, 'storeRate'])->name('shipping.rates.store');
        Route::delete('/shipping/rates/{rate}', [AdminShippingController::class, 'destroyRate'])->name('shipping.rates.destroy');

        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');

        Route::get('/product-requests', [AdminProductRequestController::class, 'index'])->name('product-requests.index');
        Route::post('/product-requests/{productRequest}', [AdminProductRequestController::class, 'update'])->name('product-requests.update');
    });
