<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\TransferController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/me', [AuthController::class, 'me']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/warehouses', [WarehouseController::class, 'index']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/error-logs', [ErrorLogController::class, 'index']);
    Route::get('/reports/products-pdf', [ReportController::class, 'productsPdf']);

    Route::get('/system/stats', function () {
        return response()->json([
            'cpu' => rand(10, 85) . '.' . rand(0, 9) . '%',
            'memory' => (rand(10, 35) / 10) . ' GB / 4 GB',
            'uptime' => '99.9%',
            'response_time' => rand(45, 150) . 'ms'
        ]);
    });

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::post('/products/import', [ProductController::class, 'import']);

    Route::apiResource('products', ProductController::class);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('suppliers', SupplierController::class);
    Route::apiResource('users', UserController::class);
    Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store']);

    Route::get('transfers', [TransferController::class, 'index']);
    Route::post('transfers', [TransferController::class, 'store']);
    Route::patch('transfers/{transfer}/approve', [TransferController::class, 'approve']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
