<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductsController;

Route::group(['middleware' => ['verify.embedded', 'verify.shopify']], function () {

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::post('/sync', [DashboardController::class, 'sync'])->name('sync');

    Route::get('/search', [DashboardController::class, 'orderSeacrhfilter'])->name('search');

    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');

});

require __DIR__ . '/auth.php';
