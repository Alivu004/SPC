<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProductsController;
use Inertia\Inertia;

Route::group(['middleware' => ['verify.shopify','verify.embedded']], function () {

    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::post('/sync', [DashboardController::class, 'sync'])->name('sync');

    Route::get('/search', [DashboardController::class, 'orderSeacrhfilter'])->name('search');

    Route::get('/products', [ProductsController::class, 'index'])->name('products.index');

    // ── Rules UI routes (static/Inertia only — no backend logic yet) ──
    Route::get('/rules',              fn () => Inertia::render('Rules/Index'))->name('rules.index');
    Route::get('/rules/create',       fn () => Inertia::render('Rules/Create'))->name('rules.create');
    Route::get('/rules/{id}/edit',    fn ($id) => Inertia::render('Rules/Edit'))->name('rules.edit');

    // Placeholder write routes — connect to controller later
    Route::post('/rules',             fn () => redirect()->route('rules.index'))->name('rules.store');
    Route::put('/rules/{id}',         fn ($id) => redirect()->route('rules.index'))->name('rules.update');
    Route::delete('/rules/{id}',      fn ($id) => redirect()->route('rules.index'))->name('rules.destroy');
    Route::post('/rules/{id}/toggle-status', fn ($id) => back())->name('rules.toggle-status');

});

require __DIR__ . '/auth.php';
