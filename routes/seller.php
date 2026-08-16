<?php

use App\Http\Controllers\Seller\OfferController;
use App\Http\Controllers\Seller\SellerDashboardController;
use App\Http\Controllers\Seller\SellerOrderController;
use App\Http\Controllers\Seller\SellerProfileController;
use App\Http\Controllers\Seller\SellerShippingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Seller dashboard
|--------------------------------------------------------------------------
| Deliberately small: prices, stock, delivery zones and orders. That is what
| a local store actually needs on day one.
*/

Route::middleware(['auth', 'role:seller,admin'])
    ->prefix('seller')
    ->name('seller.')
    ->group(function () {
        Route::get('/', [SellerDashboardController::class, 'index'])->name('dashboard');

        Route::get('/offers', [OfferController::class, 'index'])->name('offers.index');
        Route::get('/offers/create', [OfferController::class, 'create'])->name('offers.create');
        Route::post('/offers', [OfferController::class, 'store'])->name('offers.store');
        Route::get('/offers/{offer}/edit', [OfferController::class, 'edit'])->name('offers.edit');
        Route::put('/offers/{offer}', [OfferController::class, 'update'])->name('offers.update');
        Route::post('/offers/{offer}/confirm', [OfferController::class, 'confirm'])->name('offers.confirm');
        Route::delete('/offers/{offer}', [OfferController::class, 'destroy'])->name('offers.destroy');

        Route::get('/catalog/search', [OfferController::class, 'catalogSearch'])->name('catalog.search');
        Route::post('/catalog/request', [OfferController::class, 'requestProduct'])->name('catalog.request');

        Route::get('/orders', [SellerOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{sellerOrder}', [SellerOrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{sellerOrder}/transition', [SellerOrderController::class, 'transition'])->name('orders.transition');

        Route::get('/shipping', [SellerShippingController::class, 'edit'])->name('shipping.edit');
        Route::put('/shipping', [SellerShippingController::class, 'update'])->name('shipping.update');

        Route::get('/profile', [SellerProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [SellerProfileController::class, 'update'])->name('profile.update');
    });
