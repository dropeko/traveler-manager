<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\TravelOrderController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json(['ok' => true]));

    // Pública (sem JWT)
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->get('/me', function (Request $request) {
        return response()->json([
            'user' => new UserResource($request->user('api')),
        ]);
    });

    Route::middleware('auth:api')->post('/travel-orders', [TravelOrderController::class, 'createTravelOrder']);
    Route::middleware('auth:api')->get('/travel-orders', [TravelOrderController::class, 'listTravelOrders']);
    Route::middleware('auth:api')->get('/travel-orders/{order_code}', [TravelOrderController::class, 'showByOrderCode']);
    Route::middleware('auth:api')->patch('/travel-orders/{order_code}/status', [TravelOrderController::class, 'updateTravelOrderStatus']);
});