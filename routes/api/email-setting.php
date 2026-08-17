<?php

use App\Domains\Setting\Controllers\EmailSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/settings/email', [EmailSettingController::class, 'show'])
        ->middleware('platform:email-settings.view');

    Route::put('/settings/email', [EmailSettingController::class, 'update'])
        ->middleware('platform:email-settings.update');
});
