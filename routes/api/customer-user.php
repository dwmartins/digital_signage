<?php

use App\Domains\User\Controllers\CustomerUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function() {
    Route::get('/customer-users', [CustomerUserController::class, 'index'])
        ->middleware(['platform:customers.view']);

    Route::post('/customer-users', [CustomerUserController::class, 'store'])
        ->middleware(['platform:customers.create']);

    Route::put('/customer-users/{id}', [CustomerUserController::class, 'update'])
        ->middleware(['platform:customers.update']);

    Route::delete('/customer-users/{id}', [CustomerUserController::class, 'destroy'])
        ->middleware(['platform:customers.delete']);
});
