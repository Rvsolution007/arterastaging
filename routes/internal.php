<?php

use App\Http\Controllers\Api\AdLiveBusinessProfileUpdateController;
use App\Http\Controllers\Api\AdLiveBusinessCreationController;
use Illuminate\Support\Facades\Route;

Route::post('business-profile-updates', AdLiveBusinessProfileUpdateController::class)
    ->name('internal.adlive.business-profile-updates');

Route::post('businesses', AdLiveBusinessCreationController::class)
    ->name('internal.adlive.businesses');
