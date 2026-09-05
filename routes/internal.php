<?php

use App\Http\Controllers\Api\AdLiveBusinessProfileUpdateController;
use Illuminate\Support\Facades\Route;

Route::post('business-profile-updates', AdLiveBusinessProfileUpdateController::class)
    ->name('internal.adlive.business-profile-updates');
