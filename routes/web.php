<?php

use App\Http\Controllers\Api\GoogleOAuthController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('/google/oauth/callback', [GoogleOAuthController::class, 'callback'])->name('google.oauth.callback');

Route::view('/{any}', 'welcome')
    ->where('any', '.*')
    ->name('spa');
