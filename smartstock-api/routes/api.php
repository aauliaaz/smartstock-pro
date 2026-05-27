<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ErrorLogController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\StockTransferController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SystemController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Public
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Authenticated
    Route::middleware(['auth:sanctum', 'role'])->group(function () {

        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::patch('/auth/password', [AuthController::class, 'updatePassword']);

        // Dashboard (all roles)
        Route::prefix('dashboard')->group(function () {
            Route::get('/summary', [DashboardController::class, 'summary']);
            Route::get('/trends', [DashboardController::class, 'trends']);
            Route::get('/top-products', [DashboardController::class, 'topProducts']);
            Route::get('/warehouses-map', [DashboardController::class, 'warehousesMap']);
            Route::get('/alerts', [DashboardController::class, 'alerts']);
            Route::get('/distribution', [DashboardController::class, 'distribution']);
        });

        // Notifications (own data)
        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
            Route::patch('/{id}/read', [NotificationController::class, 'markRead']);
            Route::post('/mark-all-read', [NotificationController::class, 'markAllRead']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
        });

        // Products
        Route::get('/products/search', [ProductController::class, 'search']);
        Route::get('/products', [ProductController::class, 'index']);
        Route::get('/products/{id}', [ProductController::class, 'show']);
        Route::middleware('role:ADM,MGR')->group(function () {
            Route::post('/products', [ProductController::class, 'store']);
            Route::patch('/products/{id}', [ProductController::class, 'update']);
            Route::post('/products/{id}/images', [ProductController::class, 'uploadImage']);
            Route::delete('/products/{id}/images/{imageId}', [ProductController::class, 'deleteImage']);
        });
        Route::middleware('role:ADM')->delete('/products/{id}', [ProductController::class, 'destroy']);

        // Categories
        Route::get('/categories', [CategoryController::class, 'index']);
        Route::get('/categories/{id}', [CategoryController::class, 'show']);
        Route::middleware('role:ADM,MGR')->group(function () {
            Route::post('/categories', [CategoryController::class, 'store']);
            Route::patch('/categories/{id}', [CategoryController::class, 'update']);
            Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
        });

        // Warehouses
        Route::get('/warehouses', [WarehouseController::class, 'index']);
        Route::get('/warehouses/{id}', [WarehouseController::class, 'show']);
        Route::middleware('role:ADM,MGR')->group(function () {
            Route::post('/warehouses', [WarehouseController::class, 'store']);
            Route::patch('/warehouses/{id}', [WarehouseController::class, 'update']);
        });
        Route::middleware('role:ADM')->delete('/warehouses/{id}', [WarehouseController::class, 'destroy']);

        // Suppliers
        Route::get('/suppliers', [SupplierController::class, 'index']);
        Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
        Route::middleware('role:ADM,MGR')->group(function () {
            Route::post('/suppliers', [SupplierController::class, 'store']);
            Route::patch('/suppliers/{id}', [SupplierController::class, 'update']);
            Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
        });

        // Stock Movements
        Route::get('/stock-movements', [StockMovementController::class, 'index']);
        Route::middleware('role:ADM,MGR,STF')->post('/stock-movements', [StockMovementController::class, 'store']);

        // Stock Transfers
        Route::get('/transfers', [StockTransferController::class, 'index']);
        Route::get('/transfers/{id}', [StockTransferController::class, 'show']);
        Route::middleware('role:ADM,MGR,STF')->group(function () {
            Route::post('/transfers', [StockTransferController::class, 'store']);
            Route::post('/transfers/{id}/cancel', [StockTransferController::class, 'cancel']);
            Route::post('/transfers/{id}/ship', [StockTransferController::class, 'ship']);
            Route::post('/transfers/{id}/receive', [StockTransferController::class, 'receive']);
        });
        Route::middleware('role:ADM,MGR')->group(function () {
            Route::post('/transfers/{id}/approve', [StockTransferController::class, 'approve']);
            Route::post('/transfers/{id}/reject', [StockTransferController::class, 'reject']);
        });

        // Reports
        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports/generate', [ReportController::class, 'generate']);
        Route::get('/reports/{id}', [ReportController::class, 'show']);
        Route::get('/reports/{id}/download', [ReportController::class, 'download']);

        // Imports
        Route::get('/imports/template', [ImportController::class, 'template']);
        Route::middleware('role:ADM,MGR,STF')->group(function () {
            Route::get('/imports', [ImportController::class, 'index']);
            Route::post('/imports', [ImportController::class, 'store']);
            Route::get('/imports/{id}', [ImportController::class, 'show']);
            Route::get('/imports/{id}/errors', [ImportController::class, 'downloadErrors']);
        });

        // Users (Admin only)
        Route::middleware('role:ADM')->group(function () {
            Route::get('/users/roles', [UserController::class, 'roles']);
            Route::get('/users', [UserController::class, 'index']);
            Route::post('/users', [UserController::class, 'store']);
            Route::get('/users/{id}', [UserController::class, 'show']);
            Route::patch('/users/{id}', [UserController::class, 'update']);
            Route::delete('/users/{id}', [UserController::class, 'destroy']);
            Route::post('/users/{id}/reset-password', [UserController::class, 'resetPassword']);
        });

        // Audit Log (ADM full, MGR read)
        Route::middleware('role:ADM,MGR')->get('/audit-logs', [AuditLogController::class, 'index']);

        // Error Log & System (ADM only)
        Route::middleware('role:ADM')->group(function () {
            Route::get('/error-logs', [ErrorLogController::class, 'index']);
            Route::patch('/error-logs/{id}/resolve', [ErrorLogController::class, 'resolve']);
            Route::delete('/error-logs/{id}', [ErrorLogController::class, 'destroy']);
            Route::get('/system/health', [SystemController::class, 'health']);
        });
    });
});
