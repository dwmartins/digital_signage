<?php

use App\Domains\User\Controllers\SupportUserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/support-users', [SupportUserController::class, 'index'])
        ->middleware(['platform:support-users.view']);

    Route::post('/support-users', [SupportUserController::class, 'store'])
        ->middleware(['platform:support-users.create']);

    Route::put('/support-users/{id}', [SupportUserController::class, 'update'])
        ->middleware(['platform:support-users.update']);

    Route::get('/support-users/{id}/permissions', [SupportUserController::class, 'permissions'])
        ->middleware(['platform:support-users.permissions.update']);

    Route::put('/support-users/{id}/permissions', [SupportUserController::class, 'updatePermissions'])
        ->middleware(['platform:support-users.permissions.update']);

    Route::delete('/support-users/{id}', [SupportUserController::class, 'destroy'])
        ->middleware(['platform:support-users.delete']);
});
