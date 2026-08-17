<?php

use App\Domains\Setting\Controllers\StorageSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/storage', [StorageSettingController::class, 'show'])
        ->middleware('platform:storage-settings.view');

    Route::put('/settings/storage', [StorageSettingController::class, 'update'])
        ->middleware('platform:storage-settings.update');
});
