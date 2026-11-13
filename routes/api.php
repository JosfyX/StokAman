<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockTransactionController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::apiResource('categories', CategoryController::class);
Route::apiResource('suppliers', SupplierController::class);
Route::get('products/low-stock', [ProductController::class, 'lowStock']);
Route::apiResource('products', ProductController::class);

Route::post('transactions/in', [StockTransactionController::class, 'stockIn']);
Route::post('transactions/out', [StockTransactionController::class, 'stockOut']);
Route::get('products/{product}/stock-card', [StockTransactionController::class, 'stockCard']);