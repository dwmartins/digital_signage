<?php

use App\Domains\Establishment\Controllers\EstablishmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/establishments', [EstablishmentController::class, 'index'])
        ->middleware(['platform:establishments.view']);

    Route::post('/establishments', [EstablishmentController::class, 'store'])
        ->middleware(['platform:establishments.create']);

    Route::put('/establishments/{id}', [EstablishmentController::class, 'update'])
        ->middleware(['platform:establishments.update']);

    Route::delete('/establishments/{id}', [EstablishmentController::class, 'destroy'])
        ->middleware(['platform:establishments.delete']);
});
