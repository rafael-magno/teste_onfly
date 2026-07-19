<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TravelOrderController;
use App\Models\TravelOrder;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('refresh', [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::resource('travel-orders', TravelOrderController::class)
        ->only(['index', 'store', 'show']);

    Route::patch('travel-orders/{id}/status', [TravelOrderController::class, 'updateStatus'])
        ->middleware('can:updateStatus,'.TravelOrder::class);
});
