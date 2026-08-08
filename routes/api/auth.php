<?php

use App\Domains\Auth\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/auth/validate', [AuthController::class, 'validate']);
});