<?php

use App\Domains\Campaign\Controllers\CustomerSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/customer/subscriptions', [CustomerSubscriptionController::class, 'index'])
    ->middleware(['auth:sanctum', 'customer']);
