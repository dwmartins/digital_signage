<?php

use App\Domains\Profile\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::put('/profile/appearance', [ProfileController::class, 'updateAppearance']);
    Route::post('/profile/appearance/reset', [ProfileController::class, 'resetAppearance']);
});