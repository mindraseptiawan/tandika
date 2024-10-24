<?php

use App\Http\Controllers\API\KandangController;
use App\Http\Controllers\API\PemeliharaanController;
use App\Http\Controllers\API\PakanController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\SaleController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\PurchaseController;
use App\Http\Controllers\API\TransactionController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\SupplierController;
use App\Http\Controllers\API\StockMovementController;
use App\Http\Controllers\API\CashflowController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('register', [UserController::class, 'register']);
Route::post('login', [UserController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
    Route::get('user', [UserController::class, 'fetch']);
    Route::post('user', [UserController::class, 'updateProfile']);
    Route::post('logout', [UserController::class, 'logout']);
    Route::get('user/roles', [UserController::class, 'checkUserRoles']);

    Route::get('kandang', [KandangController::class, 'all']);
    Route::get('kandang/{id}', [KandangController::class, 'show']);

    Route::get('pemeliharaan', [PemeliharaanController::class, 'all']);
    Route::get('pemeliharaan/kandang/{kandang_id}', [PemeliharaanController::class, 'all']);


    Route::middleware('role:Pimpinan|Operator')->group(function () {
        Route::post('kandang', [KandangController::class, 'store']);
        Route::put('kandang/{id}', [KandangController::class, 'update']);
        Route::delete('kandang/{id}', [KandangController::class, 'destroy']);

        Route::get('pemeliharaan/{id}', [PemeliharaanController::class, 'show']);
        Route::post('pemeliharaan', [PemeliharaanController::class, 'create']);
        Route::put('pemeliharaan/{id}', [PemeliharaanController::class, 'update']);
        Route::delete('pemeliharaan/{id}', [PemeliharaanController::class, 'destroy']);

        Route::get('stocks/kandang/{kandang_id}', [StockMovementController::class, 'getByKandangId']);
        Route::get('stocks', [StockMovementController::class, 'all']);


        Route::get('suppliers', [SupplierController::class, 'all']);
        Route::get('suppliers/{id}', [SupplierController::class, 'show']);
        Route::post('suppliers', [SupplierController::class, 'create']);
        Route::put('suppliers/{id}', [SupplierController::class, 'update']);
        Route::delete('suppliers/{id}', [SupplierController::class, 'destroy']);

        Route::get('customers', [CustomerController::class, 'all']);
        Route::get('customers/{id}', [CustomerController::class, 'show']);
        Route::post('customers', [CustomerController::class, 'create']);
        Route::put('customers/{id}', [CustomerController::class, 'update']);
        Route::delete('customers/{id}', [CustomerController::class, 'destroy']);

        Route::get('transactions', [TransactionController::class, 'all']);
        Route::get('transactions/{id}', [TransactionController::class, 'show']);
        Route::post('transactions', [TransactionController::class, 'create']);
        Route::put('transactions/{id}', [TransactionController::class, 'update']);
        Route::delete('transactions/{id}', [TransactionController::class, 'destroy']);

        Route::get('purchases', [PurchaseController::class, 'all']);
        Route::get('purchases/{id}', [PurchaseController::class, 'show']);
        Route::get('purchases/supplier/{supplier_id}', [PurchaseController::class, 'getPurchasesBySupplier']);
        Route::post('purchases', [PurchaseController::class, 'create']);
        Route::post('purchases/{id}', [PurchaseController::class, 'update']);
        Route::delete('purchases/{id}', [PurchaseController::class, 'destroy']);

        Route::get('sales', [SaleController::class, 'all']);
        Route::get('sales/{id}', [SaleController::class, 'show']);
        Route::get('sales/customer/{customer_id}', [SaleController::class, 'getSalesByCustomer']);
        Route::post('/sales', [SaleController::class, 'create']);
        Route::put('sales/{id}', [SaleController::class, 'update']);
        Route::delete('sales/{id}', [SaleController::class, 'destroy']);

        Route::get('pakan', [PakanController::class, 'all']);
        Route::post('pakan', [PakanController::class, 'create']);
        Route::put('pakan/{id}', [PakanController::class, 'update']);
        Route::delete('pakan/{id}', [PakanController::class, 'destroy']);


        Route::get('orders', [OrderController::class, 'all']);
        Route::get('/orders/customer/{customer_id}', [OrderController::class, 'getOrdersByCustomer']);
        Route::post('orders', [OrderController::class, 'store']);
        Route::get('orders/{id}', [OrderController::class, 'show']);
        Route::put('orders/{id}', [OrderController::class, 'update']);
        Route::delete('orders/{id}', [OrderController::class, 'destroy']);
        Route::get('orders/status/{status}', [OrderController::class, 'getOrdersByStatus']);
        Route::post('/orders/{id}/set-price', [OrderController::class, 'setPricePerUnit']);
        Route::post('/orders/{id}/process', [OrderController::class, 'processOrder']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
        Route::post('/orders/{id}/submit-payment', [OrderController::class, 'submitPaymentProof']);
        Route::post('/orders/{id}/verify-payment', [OrderController::class, 'verifyPayment']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);
    });

    Route::middleware('role:Pimpinan')->group(function () {
        Route::get('users', [UserController::class, 'getAllUsers']);
        Route::put('user/{Id}/role', [UserController::class, 'assignRole']);
        Route::delete('user/{user}', [UserController::class, 'deleteUser']);
        Route::put('user/{Id}/deactivate', [UserController::class, 'deactivateUser']);

        Route::get('customersreport', [CustomerController::class, 'laporan']);
        Route::get('suppliersreport', [SupplierController::class, 'laporan']);
        Route::get('purchasesreport', [PurchaseController::class, 'laporan']);
        Route::get('salesreport', [SaleController::class, 'laporan']);
        Route::get('transactionsreport', [TransactionController::class, 'laporan']);
        Route::get('stocksreport', [StockMovementController::class, 'laporan']);

        Route::get('cashflows', [CashflowController::class, 'index']);
    });
});
