<?php

use App\Domains\Screen\Controllers\ScreenController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/screens', [ScreenController::class, 'index'])
        ->middleware(['platform:screens.view']);

    Route::get('/screens/establishment-options', [ScreenController::class, 'establishmentOptions'])
        ->middleware(['platform:screens.view']);

    Route::post('/screens', [ScreenController::class, 'store'])
        ->middleware(['platform:screens.create']);

    Route::put('/screens/{id}', [ScreenController::class, 'update'])
        ->middleware(['platform:screens.update']);

    Route::delete('/screens/{id}', [ScreenController::class, 'destroy'])
        ->middleware(['platform:screens.delete']);
});
