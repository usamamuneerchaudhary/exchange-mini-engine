<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::get('/profile', [App\Http\Controllers\Api\ProfileController::class, 'show']);
    Route::get('/orders', [App\Http\Controllers\Api\OrderController::class, 'index']);
    Route::get('/orders/my', [App\Http\Controllers\Api\OrderController::class, 'myOrders']);
    Route::post('/orders', [App\Http\Controllers\Api\OrderController::class, 'store']);
    Route::post('/orders/{order}/cancel', [App\Http\Controllers\Api\OrderController::class, 'cancel']);
});
