<?php

use App\Domains\Campaign\Controllers\CampaignController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index'])
        ->middleware('platform:campaigns.view');
        
    Route::get('/campaigns/options', [CampaignController::class, 'options'])
        ->middleware('platform:campaigns.view');

    Route::get('/campaigns/media-options', [CampaignController::class, 'mediaOptions'])
        ->middleware('platform:campaigns.view');

    Route::get('/campaigns/display-point-options', [CampaignController::class, 'displayPointOptions'])
        ->middleware('platform:campaigns.view');

    Route::get('/campaigns/{id}', [CampaignController::class, 'show'])
        ->middleware('platform:campaigns.view');
        
    Route::post('/campaigns', [CampaignController::class, 'store'])
        ->middleware('platform:campaigns.create');
        
    Route::put('/campaigns/{id}', [CampaignController::class, 'update'])
        ->middleware('platform:campaigns.update');

    Route::delete('/campaigns/{id}/media/{mediaId}', [CampaignController::class, 'detachMedia'])
        ->middleware('platform:campaigns.update');
        
    Route::delete('/campaigns/{id}', [CampaignController::class, 'destroy'])
        ->middleware('platform:campaigns.delete');
        
});
