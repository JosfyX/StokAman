<?php

use App\Http\Controllers\WebDashboardController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', [WebDashboardController::class, 'index'])->name('web.dashboard');
Route::post('/web/categories', [WebDashboardController::class, 'storeCategory'])->name('web.categories.store');
Route::post('/web/suppliers', [WebDashboardController::class, 'storeSupplier'])->name('web.suppliers.store');
Route::post('/web/products', [WebDashboardController::class, 'storeProduct'])->name('web.products.store');
Route::post('/web/transactions/in', [WebDashboardController::class, 'stockIn'])->name('web.stock.in');
Route::post('/web/transactions/out', [WebDashboardController::class, 'stockOut'])->name('web.stock.out');

Route::put('/web/products/{product}', [WebDashboardController::class, 'updateProduct'])->name('web.products.update');
Route::delete('/web/products/{product}', [WebDashboardController::class, 'destroyProduct'])->name('web.products.destroy');

Route::put('/web/categories/{category}', [WebDashboardController::class, 'updateCategory'])->name('web.categories.update');
Route::delete('/web/categories/{category}', [WebDashboardController::class, 'destroyCategory'])->name('web.categories.destroy');

Route::put('/web/suppliers/{supplier}', [WebDashboardController::class, 'updateSupplier'])->name('web.suppliers.update');
Route::delete('/web/suppliers/{supplier}', [WebDashboardController::class, 'destroySupplier'])->name('web.suppliers.destroy');

Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        return response()->json([
            'status'   => 'healthy',
            'database' => 'connected',
        ], 200);
    } catch (\Throwable $e) {
        return response()->json([
            'status'   => 'unhealthy',
            'database' => 'disconnected',
            'error'    => $e->getMessage(),
        ], 500);
    }
});
