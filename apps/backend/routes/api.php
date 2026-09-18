<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Carts\CartController;
use App\Http\Controllers\Carts\CartItemController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Products\ProductCategoryController;
use App\Http\Controllers\Products\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Every route lives behind auth:sanctum. There are no unauthenticated
| endpoints: reading the catalogue needs view-products / view-product-categories,
| which the customer role holds by default, and writing needs the matching
| manage-* permission, which only admins hold.
|
*/

/*
 * Registration and login are the only unauthenticated endpoints in the API.
 * They cannot be behind auth:sanctum - they exist to issue the token that
 * everything else requires - so they are rate limited instead: six attempts
 * per minute per IP, which stops credential stuffing without inconveniencing
 * anyone typing their own password.
 */
Route::middleware('throttle:6,1')->group(function () {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me', [AuthController::class, 'me']);

    // Product categories
    Route::get('product-categories', [ProductCategoryController::class, 'index']);
    Route::post('product-categories', [ProductCategoryController::class, 'store']);
    Route::get('product-categories/{productCategory}', [ProductCategoryController::class, 'show']);
    Route::patch('product-categories/{productCategory}', [ProductCategoryController::class, 'update']);
    Route::delete('product-categories/{productCategory}', [ProductCategoryController::class, 'destroy']);
    Route::patch('product-categories/{uuid}/restore', [ProductCategoryController::class, 'restore']);

    // Products
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::patch('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
    Route::patch('products/{uuid}/restore', [ProductController::class, 'restore']);

    // Cart - always the authenticated user's own, so no identifier in the path
    Route::get('cart', [CartController::class, 'show']);
    Route::delete('cart', [CartController::class, 'destroy']);
    Route::post('cart/items', [CartItemController::class, 'store']);
    Route::patch('cart/items/{cartItem}', [CartItemController::class, 'update']);
    Route::delete('cart/items/{cartItem}', [CartItemController::class, 'destroy']);

    // Orders - index is scoped to your own unless you hold manage-orders
    Route::get('orders', [OrderController::class, 'index']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);
    Route::patch('orders/{order}/cancel', [OrderController::class, 'cancel']);
});
