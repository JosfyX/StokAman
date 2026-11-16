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
