<?php

use App\Domains\Locality\Controllers\LocalityController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/localities/options', [LocalityController::class, 'options']);

    Route::get('/localities/{type}', [LocalityController::class, 'index'])
        ->middleware('platform:localities.view');

    Route::post('/localities/{type}', [LocalityController::class, 'store'])
        ->middleware('platform:localities.create');

    Route::put('/localities/{type}/{id}', [LocalityController::class, 'update'])
        ->middleware('platform:localities.update');

    Route::delete('/localities/{type}/{id}', [LocalityController::class, 'destroy'])
        ->middleware('platform:localities.delete');
});
