<?php

use App\Domains\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::put('/profile', [ProfileController::class, 'update']);
     Route::put('/profile/password', [ProfileController::class, 'updatePassword']);
     Route::post('/profile/avatar', [ProfileController::class, 'updateAvatar']);
    
    Route::put('/profile/appearance', [ProfileController::class, 'updateAppearance']);
    Route::post('/profile/appearance/reset', [ProfileController::class, 'resetAppearance']);

    Route::get('/profile/sessions', [ProfileController::class, 'sessions']);
    Route::delete('/profile/sessions/{id}', [ProfileController::class, 'removeSession']);
});