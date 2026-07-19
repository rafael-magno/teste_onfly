<?php

use App\Http\Controllers\TravelOrderController;
use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

Route::middleware('auth:api')->group(function () {
    Route::resource('travel-orders', TravelOrderController::class)
        ->only(['index', 'store', 'show']);

    Route::patch('travel-orders/{travelOrder}/status', [TravelOrderController::class, 'updateStatus'])
        ->middleware('can:updateStatus,'.TravelOrder::class);
});
