<?php

use App\Domains\Campaign\Controllers\CustomerCampaignController;
use App\Domains\Dashboard\Controllers\CustomerCampaignOnboardingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'customer'])->group(function () {
    Route::get('/customer/campaigns', [CustomerCampaignController::class, 'index']);
    Route::get('/customer/campaigns/options', [CustomerCampaignController::class, 'options']);
    Route::get('/customer/campaigns/media/{id}/content', [CustomerCampaignController::class, 'content']);
    Route::get('/customer/campaigns/{id}', [CustomerCampaignController::class, 'show']);
    Route::patch('/customer/campaigns/{id}/status', [CustomerCampaignController::class, 'updateStatus']);

    Route::get('/customer/campaign-onboarding/options', [CustomerCampaignOnboardingController::class, 'options']);
    Route::get('/customer/campaign-onboarding/media/{id}/content', [CustomerCampaignOnboardingController::class, 'content']);
    Route::post('/customer/campaign-onboarding', [CustomerCampaignOnboardingController::class, 'store']);
});
