<?php

use App\Domains\Plan\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/plans', [PlanController::class, 'index'])->middleware('platform:plans.view');
    Route::post('/plans', [PlanController::class, 'store'])->middleware('platform:plans.create');
    Route::put('/plans/{id}', [PlanController::class, 'update'])->middleware('platform:plans.update');
    Route::delete('/plans/{id}', [PlanController::class, 'destroy'])->middleware('platform:plans.delete');
});
