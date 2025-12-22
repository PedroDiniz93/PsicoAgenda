<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GoogleOAuthController;

Route::view('/', 'welcome');

Route::get('/google/oauth/callback', [GoogleOAuthController::class, 'callback'])->name('google.oauth.callback');

Route::view('/{any}', 'welcome')
    ->where('any', '.*')
    ->name('spa');
