<?php

use App\Http\Controllers\Api\AuditLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ConsultationController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MedicineRequestController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\SalesController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/setup-status', [AuthController::class, 'setupStatus']);
Route::post('/setup-owner', [AuthController::class, 'setupOwner']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::middleware('active')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', DashboardController::class);
        Route::apiResource('users', UserController::class)
            ->middleware('role:owner,admin_salam_sehat,admin_badan_sehat');
        Route::apiResource('products', ProductController::class);
        Route::patch('/products/{product}/stock', [ProductController::class, 'adjustStock']);
        Route::apiResource('suppliers', SupplierController::class);
        Route::get('/sales', [SalesController::class, 'index']);
        Route::post('/sales', [SalesController::class, 'store']);
        Route::get('/sales/{sale}', [SalesController::class, 'show']);
        Route::get('/customers', [CustomerController::class, 'index']);
        Route::post('/customers', [CustomerController::class, 'store']);
        Route::get('/customers/{customer}', [CustomerController::class, 'show']);
        Route::match(['put', 'patch'], '/customers/{customer}', [CustomerController::class, 'update']);
        Route::get('/medicine-requests', [MedicineRequestController::class, 'index']);
        Route::post('/medicine-requests', [MedicineRequestController::class, 'store']);
        Route::patch('/medicine-requests/{medicineRequest}', [MedicineRequestController::class, 'update']);
        Route::post('/consultations/triage', [ConsultationController::class, 'triage'])
            ->middleware('role:owner,admin_salam_sehat,admin_badan_sehat,cashier')
            ->middleware('throttle:10,1');
        Route::get('/reports', ReportController::class);
        Route::get('/audit-logs', AuditLogController::class);
        Route::get('/stock-movements', [StockMovementController::class, 'index']);
        Route::get('/purchases', [PurchaseController::class, 'index']);
        Route::post('/purchases', [PurchaseController::class, 'store']);
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show']);
        Route::post('/purchases/{purchase}/receive', [PurchaseController::class, 'receive']);
        Route::post('/purchases/{purchase}/cancel', [PurchaseController::class, 'cancel']);
    });
});
