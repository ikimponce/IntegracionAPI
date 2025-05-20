<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ClientOrderController;
use App\Http\Controllers\WarehouseOrderController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/students', function () { return 'students list';  });

// rutas crud de productos
Route::apiResource('products', ProductController::class);

// rutas pedidos a bodega
Route::post('warehouse-orders', [WarehouseOrderController::class, 'store']);
Route::post('/warehouse-orders/{id}/cancel', [WarehouseOrderController::class, 'cancel']);

// rutas pedidos webpay
Route::post('/client-orders', [ClientOrderController::class, 'store']);
Route::post('/client-orders/{id}/webpay/response', [ClientOrderController::class, 'webpayResponse']);
Route::get('/client-orders/{id}/complete', [ClientOrderController::class, 'completeOrder']);
