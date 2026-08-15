<?php

use App\Domains\Media\Controllers\MediaAssetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/media-assets', [MediaAssetController::class, 'index'])
        ->middleware(['platform:media.view']);
        
    Route::get('/media-assets/customer-options', [MediaAssetController::class, 'customerOptions'])
        ->middleware(['platform:media.view']);
        
    Route::get('/media-assets/{id}/content', [MediaAssetController::class, 'content'])
        ->middleware(['platform:media.view']);

    Route::get('/media-assets/{id}/history', [MediaAssetController::class, 'history'])
        ->middleware(['platform:media.view']);
        
    Route::post('/media-assets', [MediaAssetController::class, 'store'])
        ->middleware(['platform:media.create']);
        
    Route::put('/media-assets/{id}', [MediaAssetController::class, 'update'])
        ->middleware(['platform:media.update']);
        
    Route::patch('/media-assets/{id}/approval', [MediaAssetController::class, 'updateApproval'])
        ->middleware(['platform:media.approve']);
        
    Route::delete('/media-assets/{id}', [MediaAssetController::class, 'destroy'])
        ->middleware(['platform:media.delete']);
        
});
