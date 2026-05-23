<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/phonepe-callback', 'Api\HomeApi@phonepe_callback');

// TEMPORARY DEBUG ROUTE
Route::post('/client-debug-log', function(Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::info("CLIENT JS DEBUG:", $request->all());
    return response()->json(['success' => true]);
});

Route::
        namespace('Api')->middleware(['throttle'])->group(function () {
            Route::post('/login', 'AuthApi@login');
            Route::post('/registration', 'AuthApi@registration');
            Route::post('/google-registration', 'AuthApi@google_registration');
            Route::post('/phone-login', 'AuthApi@phone_login');
            Route::post('/forgot-password', 'AuthApi@forgot_password');
        });

Route::
        namespace('Api')->middleware(['throttle'])->group(function () {
            Route::post('/change-password', 'AuthApi@change_password');
            // Route::post('/register-fcm', 'AuthApi@register_fcm');
            // Route::post('/logout', 'AuthApi@logout');
            Route::post('/verify-account', 'AuthApi@verifyAccount');
            Route::post('/resend-verify-code', 'AuthApi@resendVerifyCode');

            Route::get('/user', 'AuthApi@user_data');
            Route::post('/user_data', 'AuthApi@user_data');
            Route::post('/profile-update', 'AuthApi@profile_update');
            Route::post('/user-account-delete', 'AuthApi@delete_user_account');
            Route::post('/report-error', 'AuthApi@reportError');
            Route::post('/track-activity', 'AuthApi@trackActivity')->name('api.track-activity');
            Route::post('/track-ad-events', 'AuthApi@trackAdEvents')->name('api.track-ad-events');

            Route::get('/get-home-data', 'HomeApi@getHomeData');
            Route::get('/story', 'ContentApiController@getStory');
            Route::get('/festival', 'ContentApiController@getFestival');
            Route::get('/category', 'ContentApiController@getCategory');
            Route::get('/custom-post', 'HomeApi@customPost');
            Route::get('/personal', 'HomeApi@personal');
            Route::post('/search', 'HomeApi@search');

            Route::get('/news', 'ContentApiController@getNews');
            Route::get('/business', 'HomeApi@getBusiness');
            Route::post('/add-business', 'HomeApi@addBusiness');
            Route::post('/update-business', 'HomeApi@updateBusiness');
            Route::post('/delete-business', 'HomeApi@deleteBusiness');
            Route::get('/get-post', 'HomeApi@getPost');

            Route::get('/language', 'HomeApi@getLanguage');
            Route::get('/app-translations', 'HomeApi@getAppTranslations');
            Route::get('/subscription-plan', 'HomeApi@getSubscriptionplan');

            Route::post('/create-payment', 'HomeApi@addPayment');
            Route::post('stripe-payment', 'HomeApi@stripePayment');
            Route::post('paytm-payment', 'HomeApi@paytmPayment');
            // Route::post('verify-Paytm-payment','HomeApi@verifyPaytmPayment');
            Route::post('offline-payment', 'HomeApi@offlinePayment');
            Route::get('/payment-details', 'HomeApi@getPaymentDetails');
            Route::post('/create-order-cashfree', 'HomeApi@create_order_cashfree');
            Route::post('get-val', 'HomeApi@get_val');

            Route::get('/contact-subject', 'HomeApi@getContactSubject');
            Route::post('/contact-massage', 'HomeApi@postContacts');
            Route::get('/app-about', 'HomeApi@getAppAbout');
            Route::post('/set-default-business', 'HomeApi@setDefaultBusiness');

            Route::get('/custom-category', 'HomeApi@getCustomCategory');
            Route::get('/custom-frame', 'HomeApi@getCustomFrame')->middleware('saas.limit:custom_post');
            Route::post('/custom-frame/swap-product', 'HomeApi@swapProduct');

            Route::get('/business-category', 'HomeApi@getBusinessCategory');
            Route::get('/business-sub-category', 'HomeApi@getBusinessSubCategory');
            Route::get('/business-frame', 'HomeApi@getBusinessFrame');

            Route::get('/get-sticker', 'HomeApi@getSticker');
            Route::post('/search-sticker', 'HomeApi@searchSticker');

            Route::get('/product-category', 'HomeApi@getProductCategory');
            Route::get('/product', 'HomeApi@getProduct');
            Route::post('/inquiry', 'HomeApi@postInquiry');
            Route::get('/poster-category', 'HomeApi@posterCategory');
            Route::post('/poster-json', 'HomeApi@getPosterJson');
            Route::post('/withdraw-request', 'HomeApi@withdraw_request');
            Route::get('/referral-detail', 'HomeApi@referral_detail');

            Route::get('/user-custom-frame', 'HomeApi@userCustomFrame');

            Route::get('/get-video', 'HomeApi@getVideo');
            Route::post('/coupon-code-validation', 'HomeApi@coupon_code_validation');
            Route::post('/profile-card', 'HomeApi@profile_card');
            Route::post('/profile-card-image-upload', 'HomeApi@profile_card_image_upload');
            Route::get('/business-card-list', 'HomeApi@business_card_list');

            Route::Post('whatsapp-api', 'HomeApi@whatsapp_api');
            Route::post('whatsapp-otp', 'HomeApi@whatsapp_otp');

            // SaaS Limit Consumption Endpoint
            Route::post('consume-feature', 'HomeApi@consumeFeature');

            // AI Magic Cloner Integration (Phase 7 SaaS Blueprint)
            Route::post('ai-magic-cloner', 'AiMagicClonerController@cloneVibe')->middleware('saas.limit:magic_cloner');

            // Gamification & Challenges
            Route::get('design-challenges', 'DesignChallengeApiController@getActiveChallenges');
            Route::post('design-challenges/submit', 'DesignChallengeApiController@submitChallenge');

            // Native App: Get frames for a specific festival/category/custom post
            Route::get('/get-frames', 'HomeApi@getFrames');
            // Setup Wizard Endpoints
            Route::post('/setup-wizard/status', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'status']);
            Route::post('/setup-wizard/analyze', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'analyze']);
            Route::post('/setup-wizard/import-columns', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'importColumns']);
            Route::post('/setup-wizard/extract-products', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'extractProducts']);
            Route::post('/setup-wizard/import-products', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'importProducts']);
            Route::post('/setup-wizard/reset', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'reset']);

            // Native Products Management
            Route::post('/products/list', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'getProducts']);
            Route::post('/products/{id}/update', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'updateProduct']);
            Route::post('/products/{id}/delete', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'deleteProduct']);

            // Catalogue Columns Management
            Route::post('/catalogue-columns', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'getColumns']);
            Route::post('/catalogue-columns/update', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'updateColumn']);
            Route::post('/catalogue-columns/{id}/delete', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'deleteColumn']);
            Route::post('/catalogue-columns/reorder', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'reorderColumns']);
            Route::post('/catalogue-columns/{id}/toggle', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'toggleColumn']);

            // Favorite Frames
            Route::post('/toggle-favorite-frame', 'FrameApiController@toggleFavorite');
            Route::get('/favorite-frames', 'FrameApiController@getFavorites');
            Route::get('/get-all-frames', 'FrameApiController@getAllFrames');

            // Favorite Frames (userId-based, for Flutter app)
            Route::post('/user-favorite-frame', 'HomeApi@userFavoriteFrame');
            Route::get('/user-favorites', 'HomeApi@userFavorites');

            // Ad Configuration Endpoint — serves all AdMob/network settings to Flutter
            Route::get('/ad-config', 'HomeApi@getAdConfig');

            // Notifications
            Route::get('/notifications', 'HomeApi@getNotifications');

            // AI Knowledge Base Endpoints (RAG)
            Route::post('/knowledge-base/ingest', [\App\Http\Controllers\KnowledgeBaseController::class, 'ingest']);
            Route::post('/knowledge-base/search', [\App\Http\Controllers\KnowledgeBaseController::class, 'search']);

            // AI Customer Support Chat Endpoints
            Route::get('/tickets', [\App\Http\Controllers\Api\AiChatController::class, 'getTickets']);
            Route::post('/ai-chat/send', [\App\Http\Controllers\Api\AiChatController::class, 'sendMessage']);
            Route::post('/ai-chat/history', [\App\Http\Controllers\Api\AiChatController::class, 'getHistory']);

            // Partner System
            Route::post('/get-partner-dashboard', 'HomeApi@getPartnerDashboard');
            Route::post('/partner-withdraw-request', 'HomeApi@partnerWithdrawRequest');
        });

Route::middleware('auth:api')->post('/user', function (Request $request) {
    return $request->user();
});

// Task 12: Dunning Webhook
Route::post('webhooks/payment/failed', 'Api\PaymentWebhookController@handle');


// Phase 3 Remaining Endpoints
Route::middleware('auth:sanctum')->group(function () {
    Route::post('onboarding/step', 'Api\OnboardingController@completeStep');
    Route::get('feedback/check-eligibility', 'Api\FeedbackController@checkEligibility');
    Route::post('feedback/submit', 'Api\FeedbackController@submit');
});
