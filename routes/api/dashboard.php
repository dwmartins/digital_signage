<?php

use App\Domains\Dashboard\Controllers\PlatformDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/platform/dashboard', [PlatformDashboardController::class, 'index'])
    ->middleware(['auth:sanctum', 'platform']);
