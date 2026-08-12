<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\PurchaseController;
// use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
// use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\PurchaseItemController;
use App\Http\Controllers\Api\SaleController;
use App\Http\Controllers\Api\SaleItemController;
use App\Http\Controllers\Api\StockTransactionController;
use App\Http\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);


    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    */

    Route::apiResource('categories', CategoryController::class);


    /*
    |--------------------------------------------------------------------------
    | Suppliers
    |--------------------------------------------------------------------------
    */

    Route::apiResource('suppliers', SupplierController::class);


    /*
    |--------------------------------------------------------------------------
    | Products
    |--------------------------------------------------------------------------
    */

    Route::apiResource('products', ProductController::class);


    /*
    |--------------------------------------------------------------------------
    | Purchases
    |--------------------------------------------------------------------------
    */

    Route::apiResource('purchases',PurchaseController::class);

  

    Route::apiResource(
        'purchase-items',
        PurchaseItemController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Sales
    |--------------------------------------------------------------------------
    */

    Route::apiResource('sales', SaleController::class);

    Route::apiResource(
        'sale-items',
        SaleItemController::class
    );


    /*
    |--------------------------------------------------------------------------
    | Stock Transactions
    |--------------------------------------------------------------------------
    */

    Route::apiResource(
        'stock-transactions',
        StockTransactionController::class
    );
});