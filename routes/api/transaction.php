<?php

use App\Domains\Billing\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/transactions', [TransactionController::class, 'index'])
        ->middleware(['platform:transactions.view']);
});

