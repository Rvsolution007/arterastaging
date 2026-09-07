<?php

use App\Http\Controllers\Api\AdLiveBusinessProfileUpdateController;
use App\Http\Controllers\Api\AdLiveBusinessCreationController;
use App\Http\Controllers\Api\AdLiveCredentialsVerificationController;
use App\Http\Controllers\Api\AdLiveIdentityController;
use Illuminate\Support\Facades\Route;

Route::post('business-profile-updates', AdLiveBusinessProfileUpdateController::class)
    ->name('internal.adlive.business-profile-updates');

Route::post('businesses', AdLiveBusinessCreationController::class)
    ->name('internal.adlive.businesses');

Route::post('credentials/verify', [AdLiveCredentialsVerificationController::class, 'verify'])
    ->middleware('throttle:login')
    ->name('internal.adlive.credentials.verify');

Route::post('identities/create', [AdLiveIdentityController::class, 'create'])
    ->name('internal.adlive.identities.create');

Route::post('identities/update', [AdLiveIdentityController::class, 'update'])
    ->name('internal.adlive.identities.update');

Route::post('identities/delete', [AdLiveIdentityController::class, 'delete'])
    ->name('internal.adlive.identities.delete');

Route::post('credentials/change', [AdLiveIdentityController::class, 'changeCredentials'])
    ->name('internal.adlive.credentials.change');

Route::post('credentials/admin-reset', [AdLiveIdentityController::class, 'adminResetCredentials'])
    ->name('internal.adlive.credentials.admin-reset');
