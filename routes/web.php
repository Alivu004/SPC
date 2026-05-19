<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\ClassificationRuleController;
use Inertia\Inertia;

Route::group(['middleware' => ['verify.shopify','verify.embedded']], function () {

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::post('/sync', [DashboardController::class, 'sync'])->name('sync');

    Route::get('/search', [DashboardController::class, 'orderSeacrhfilter'])->name('search');

    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');

    // ── Classification Rules ──────────────────────────────────────────────────
    Route::get('/rules',                          [ClassificationRuleController::class, 'index'])->name('rules.index');
    Route::get('/rules/create',                   [ClassificationRuleController::class, 'create'])->name('rules.create');
    Route::post('/rules',                         [ClassificationRuleController::class, 'store'])->name('rules.store');
    Route::get('/rules/{rule}/edit',              [ClassificationRuleController::class, 'edit'])->name('rules.edit');
    Route::put('/rules/{rule}',                   [ClassificationRuleController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{rule}',                [ClassificationRuleController::class, 'destroy'])->name('rules.destroy');
    Route::post('/rules/{rule}/toggle-status',    [ClassificationRuleController::class, 'toggleStatus'])->name('rules.toggle-status');

});

require __DIR__ . '/auth.php';
