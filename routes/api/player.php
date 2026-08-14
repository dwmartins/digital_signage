<?php

use App\Domains\Player\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/players', [PlayerController::class, 'index'])
        ->middleware(['platform:players.view']);
        
    Route::get('/players/filter-options', [PlayerController::class, 'filterOptions'])
        ->middleware(['platform:players.view']);
        
    Route::post('/players', [PlayerController::class, 'store'])
        ->middleware(['platform:players.create']);
        
    Route::put('/players/{id}', [PlayerController::class, 'update'])
        ->middleware(['platform:players.update']);
        
    Route::delete('/players/{id}', [PlayerController::class, 'destroy'])
        ->middleware(['platform:players.delete']);
        
});
