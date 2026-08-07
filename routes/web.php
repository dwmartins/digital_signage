<?php

use Illuminate\Support\Facades\Route;

/**
 * Demais rotas incia o Vue
 */
Route::get('/{any}', function () {
    return view('base');
})->where('any', '^(?!api).*');
