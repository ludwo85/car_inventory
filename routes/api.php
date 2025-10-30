<?php

use App\Http\Controllers\Api\CarController;
use App\Http\Controllers\Api\PartController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('api')->group(function () {
    Route::apiResource('cars', CarController::class);
    Route::apiResource('parts', PartController::class);
    Route::get('cars-all', [CarController::class, 'all']);
    Route::post('cars/{car}/restore', [CarController::class, 'restore']);
    Route::post('parts/{part}/restore', [PartController::class, 'restore']);
});
