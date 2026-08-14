<?php

use App\Domains\DisplayPoint\Controllers\DisplayPointController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/display-points', [DisplayPointController::class, 'index'])
        ->middleware(['platform:display-points.view']);
        
    Route::get('/display-points/options', [DisplayPointController::class, 'options'])
        ->middleware(['platform:display-points.view']);
        
    Route::post('/display-points', [DisplayPointController::class, 'store'])
        ->middleware(['platform:display-points.create']);
        
    Route::put('/display-points/{id}', [DisplayPointController::class, 'update'])
        ->middleware(['platform:display-points.update']);
        
    Route::delete('/display-points/{id}', [DisplayPointController::class, 'destroy'])
        ->middleware(['platform:display-points.delete']);
        
});
