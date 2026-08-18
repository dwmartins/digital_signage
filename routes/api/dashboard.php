<?php

use App\Domains\Dashboard\Controllers\CustomerDashboardController;
use App\Domains\Dashboard\Controllers\CustomerCampaignOnboardingController;
use App\Domains\Dashboard\Controllers\PlatformDashboardController;
use App\Domains\Campaign\Controllers\CustomerSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/platform/dashboard', [PlatformDashboardController::class, 'index'])
    ->middleware(['auth:sanctum', 'platform']);

Route::get('/customer/dashboard', [CustomerDashboardController::class, 'index'])
    ->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/customer/subscriptions', [CustomerSubscriptionController::class, 'index']);
    Route::get('/customer/campaign-onboarding/options', [CustomerCampaignOnboardingController::class, 'options']);
    Route::get('/customer/campaign-onboarding/media/{id}/content', [CustomerCampaignOnboardingController::class, 'content']);
    Route::post('/customer/campaign-onboarding', [CustomerCampaignOnboardingController::class, 'store']);
});
