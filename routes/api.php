<?php

use App\Http\Controllers\ExerciseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::post('exercise-1-artwork-version',[ExerciseController::class, 'exercise1Artwork']);
Route::post('exercise-2-tier-pricing',[ExerciseController::class, 'exercise2TierPricing']);
Route::post('exercise-3-cart-validator',[ExerciseController::class, 'exercise3CartValidator']);
Route::post('exercise-4-vendor-allocation',[ExerciseController::class, 'exercise4VendorAllocation']);
Route::post('exercise-5-discount',[ExerciseController::class, 'exercise5Discount']);
Route::post('exercise-6-approval-flow',[ExerciseController::class, 'exercise6ApprovalFlow']);
Route::post('exercise-7-inventory',[ExerciseController::class, 'exercise7Inventory']);
Route::post('exercise-8-shipment',[ ExerciseController::class, 'exercise8Shipment']);
Route::post('exercise-9-webhook',[ExerciseController::class, 'exercise9Webhook']);
Route::post('exercise-10-quote-expiry',[ExerciseController::class, 'exercise10QuoteExpiry']);
Route::post('exercise-11-product-visibility',[ExerciseController::class, 'exercise11ProductVisibility']);
Route::post('exercise-12-bundle-pricing',[ExerciseController::class, 'exercise12BundlePricing']);
Route::post('exercise-13-cart-merge',[ExerciseController::class, 'exercise13CartMerge']);
Route::post('exercise-14-upsell',[ExerciseController::class, 'exercise14Upsell']);
Route::post('exercise-15-shipping-rule',[ExerciseController::class, 'exercise15ShippingRule']);
Route::post('exercise-16-fraud-check',[ExerciseController::class, 'exercise16FraudCheck']);
// Route::post('exercise-17-shopify-price-adjustment',[ExerciseController::class, 'exercise17ShopifyPriceAdjustment']);
Route::post('exercise-19-variant-control',[ExerciseController::class, 'exercise19VariantControl']);
Route::post('exercise-18-data-sync',[ExerciseController::class, 'exercise18DataSync']);
Route::post('exercise-20-order-state',[ExerciseController::class, 'exercise20OrderState']);
