<?php

use App\Http\Controllers\Api\AdLiveBusinessProfileUpdateController;
use App\Http\Controllers\Api\AdLiveBusinessCreationController;
use App\Http\Controllers\Api\AdLiveCredentialsVerificationController;
use Illuminate\Support\Facades\Route;

Route::post('business-profile-updates', AdLiveBusinessProfileUpdateController::class)
    ->name('internal.adlive.business-profile-updates');

Route::post('businesses', AdLiveBusinessCreationController::class)
    ->name('internal.adlive.businesses');

Route::post('credentials/verify', [AdLiveCredentialsVerificationController::class, 'verify'])
    ->middleware('throttle:login')
    ->name('internal.adlive.credentials.verify');
