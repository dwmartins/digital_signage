<?php

use App\Domains\Dashboard\Controllers\CustomerDashboardController;
use App\Domains\Dashboard\Controllers\PlatformDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/platform/dashboard', [PlatformDashboardController::class, 'index'])
    ->middleware(['auth:sanctum', 'platform']);

Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])
    ->middleware('auth:sanctum');
