<?php

use App\Domains\Campaign\Controllers\CampaignSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/campaign-subscriptions', [CampaignSubscriptionController::class, 'index'])
        ->middleware('platform:subscriptions.view');

    Route::get('/campaign-subscriptions/options', [CampaignSubscriptionController::class, 'options'])
        ->middleware('platform:subscriptions.view');

    Route::post('/campaign-subscriptions', [CampaignSubscriptionController::class, 'store'])
        ->middleware('platform:subscriptions.create');
        
    Route::put('/campaign-subscriptions/{id}', [CampaignSubscriptionController::class, 'update'])
        ->middleware('platform:subscriptions.update');

    Route::patch('/campaign-subscriptions/{id}/approve', [CampaignSubscriptionController::class, 'approve'])
        ->middleware('platform:subscriptions.approve');

    Route::patch('/campaign-subscriptions/{id}/cancel', [CampaignSubscriptionController::class, 'cancel'])
        ->middleware('platform:subscriptions.cancel');

});
