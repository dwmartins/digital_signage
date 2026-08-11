<?php

use App\Domains\Category\Controllers\CategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/categories', [CategoryController::class, 'index'])
        ->middleware(['platform:categories.view']);

    Route::post('/categories', [CategoryController::class, 'store'])
        ->middleware(['platform:categories.create']);

    Route::put('/categories/{id}', [CategoryController::class, 'update'])
        ->middleware(['platform:categories.update']);

    Route::delete('/categories/{id}', [CategoryController::class, 'destroy'])
        ->middleware(['platform:categories.delete']);
});
