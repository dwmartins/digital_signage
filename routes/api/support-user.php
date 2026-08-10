<?php

use App\Domains\User\Controllers\SupportUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/support-users', [SupportUserController::class, 'index'])
        ->middleware(['platform:support-users.view']);
    
    Route::post('/support-users', [SupportUserController::class, 'store'])
        ->middleware(['platform:support-users.create']);
});