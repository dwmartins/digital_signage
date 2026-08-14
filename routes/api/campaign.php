<?php

use App\Domains\Campaign\Controllers\CampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index'])
        ->middleware('platform:campaigns.view');
        
    Route::get('/campaigns/options', [CampaignController::class, 'options'])
        ->middleware('platform:campaigns.view');
        
    Route::post('/campaigns', [CampaignController::class, 'store'])
        ->middleware('platform:campaigns.create');
        
    Route::put('/campaigns/{id}', [CampaignController::class, 'update'])
        ->middleware('platform:campaigns.update');
        
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])
        ->middleware('platform:campaigns.delete');
        
});
