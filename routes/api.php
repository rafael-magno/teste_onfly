<?php

use App\Http\Controllers\TravelOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::middleware('auth:api')->group(function () {
    Route::post('travel-orders', [TravelOrderController::class, 'store']);

    Route::patch('travel-orders/{travelOrder}/status', [TravelOrderController::class, 'updateStatus'])
        ->middleware('can:updateStatus,travelOrder');
});
