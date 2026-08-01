<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/phonepe-callback', 'Api\HomeApi@phonepe_callback');



Route::
        namespace('Api')->middleware(['throttle:login'])->group(function () {
            Route::post('/login', 'AuthApi@login');
            Route::post('/registration', 'AuthApi@registration');
            Route::post('/google-registration', 'AuthApi@google_registration');
            Route::post('/phone-login', 'AuthApi@phone_login');
        });

Route::namespace('Api')->middleware(['throttle:google-login'])->group(function () {
    Route::post('/google-sign-in', 'AuthApi@google_sign_in');
});

// Install telemetry is sent during the splash screen, before a guest has a
// mobile access token. Keep it public but tightly rate-limited; the controller
// never trusts a caller-supplied user ID without a matching bearer token.
Route::namespace('Api')->middleware(['throttle:20,1'])->group(function () {
    Route::post('/analytics/install', 'AppInstallAnalyticsController@recordInstall')->name('api.analytics.install');
});

Route::
        namespace('Api')->middleware(['throttle:password-reset'])->group(function () {
            Route::post('/forgot-password', 'AuthApi@forgot_password');
            Route::post('/forgot-password/verify-otp', 'AuthApi@verify_forgot_password_otp');
            Route::post('/forgot-password/update', 'AuthApi@update_forgot_password');
        });

Route::
        namespace('Api')->middleware(['throttle', 'mobile.request'])->group(function () {
            Route::post('/change-password', 'AuthApi@change_password')->middleware('mobile.auth');
            Route::post('/register-fcm', 'AuthApi@register_fcm');
            Route::post('/logout', 'AuthApi@logout')->middleware('mobile.auth');
            Route::post('/verify-account', 'AuthApi@verifyAccount')->middleware('throttle:email-verification');
            Route::post('/resend-verify-code', 'AuthApi@resendVerifyCode')->middleware('throttle:email-verification');

            Route::get('/user', 'AuthApi@user_data')->middleware('mobile.auth');
            Route::post('/user_data', 'AuthApi@user_data')->middleware('mobile.auth');
            Route::post('/use-reward-credit', 'AuthApi@useRewardCredit');
            Route::post('/generate-webview-url', 'AuthApi@generateWebviewUrl');
            Route::post('/profile-update', 'AuthApi@profile_update');
            Route::post('/user-account-delete', 'AuthApi@delete_user_account');
            Route::post('/report-error', 'AuthApi@reportError');
            Route::post('/track-activity', 'AuthApi@trackActivity')->name('api.track-activity');
            Route::post('/track-ad-events', 'AuthApi@trackAdEvents')->name('api.track-ad-events');
            Route::get('/get-home-data', 'HomeApi@getHomeData');
            Route::get('/story', 'ContentApiController@getStory');
            Route::get('/festival', 'ContentApiController@getFestival');
            Route::get('/category', 'ContentApiController@getCategory');
            Route::get('/custom-post-category', 'HomeApi@customPost');
            Route::get('/custom-post-category-paginated', 'HomeApi@customPostPaginated');
            Route::get('/personal', 'HomeApi@personal');
            Route::post('/search', 'HomeApi@search');

            Route::get('/news', 'ContentApiController@getNews');
            Route::get('/business', 'HomeApi@getBusiness');
            Route::post('/add-business', 'HomeApi@addBusiness');
            Route::post('/update-business', 'HomeApi@updateBusiness');
            Route::post('/delete-business', 'HomeApi@deleteBusiness');
            Route::get('/business-products', 'HomeApi@getBusinessProducts');
            Route::post('/business-products/search', 'HomeApi@searchBusinessProducts');
            Route::post('/business-products/request-custom', 'HomeApi@requestCustomProduct');
            Route::get('/get-post', 'HomeApi@getPost');

            Route::get('/language', 'HomeApi@getLanguage');
            Route::get('/app-translations', 'HomeApi@getAppTranslations');
            Route::get('/subscription-plan', 'HomeApi@getSubscriptionplan');
            Route::get('/subscription-upgrade-preview', 'HomeApi@getSubscriptionUpgradePreview');

            // Mini Website API
            Route::get('/mini-website/templates', 'MiniWebsiteApiController@templates');
            Route::post('/mini-website/generate', 'MiniWebsiteApiController@generate');
            Route::post('/mini-website/update/{id}', 'MiniWebsiteApiController@update');
            Route::get('/mini-website/my-links', 'MiniWebsiteApiController@myLinks');
            Route::post('/mini-website/delete/{id}', 'MiniWebsiteApiController@delete');

            Route::post('/create-payment', 'HomeApi@addPayment');
            Route::post('stripe-payment', 'HomeApi@stripePayment');
            Route::post('paytm-payment', 'HomeApi@paytmPayment');
            // Route::post('verify-Paytm-payment','HomeApi@verifyPaytmPayment');
            Route::post('offline-payment', 'HomeApi@offlinePayment');
            Route::get('/payment-details', 'HomeApi@getPaymentDetails');
            Route::get('/payment-history', 'HomeApi@getPaymentHistory');
            Route::post('/create-order-cashfree', 'HomeApi@create_order_cashfree');
            // Security fix: Route::post('get-val', 'HomeApi@get_val');

            Route::get('/contact-subject', 'HomeApi@getContactSubject');
            Route::post('/contact-massage', 'HomeApi@postContacts');
            Route::get('/app-about', 'HomeApi@getAppAbout');
            Route::post('/set-default-business', 'HomeApi@setDefaultBusiness');

            Route::get('/custom-category', 'HomeApi@getCustomCategory');
            Route::get('/custom-frame', 'HomeApi@getCustomFrame')->middleware('saas.limit:custom_post');
            Route::post('/custom-frame/swap-product', 'HomeApi@swapProduct');

            Route::get('/business-category', 'HomeApi@getBusinessCategory');
            Route::get('/business-sub-category', 'HomeApi@getBusinessSubCategory');
            Route::get('/business-type', 'HomeApi@getBusinessType');
            Route::get('/business-profile/search', 'HomeApi@searchBusinessProfile');
            Route::get('/custom-post', 'HomeApi@getBusinessFrame');

            Route::get('/get-sticker', 'HomeApi@getSticker');
            Route::post('/save-fcm-token', [\App\Http\Controllers\Api\HomeApi::class, 'saveFcmToken']);

            // Golden Render capture from Flutter
            Route::post('/golden-render/capture-native', [\App\Http\Controllers\Api\HomeApi::class, 'captureNativeGolden'])->middleware('throttle');

            Route::post('/inquiry', 'HomeApi@postInquiry');
            Route::get('/poster-category', 'HomeApi@posterCategory');
            Route::post('/poster-json', 'HomeApi@getPosterJson');
            Route::post('/withdraw-request', 'HomeApi@withdraw_request');
            Route::get('/referral-detail', 'HomeApi@referral_detail');

            Route::get('/user-custom-frame', 'HomeApi@userCustomFrame');
            Route::post('/editor/ai-content/generate', 'HomeApi@generateAiContent');

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


            // Gamification & Challenges
            Route::get('design-challenges', 'DesignChallengeApiController@getActiveChallenges');
            Route::post('design-challenges/submit', 'DesignChallengeApiController@submitChallenge');
            Route::get('user-achievements', 'DesignChallengeApiController@getAchievements');

            // Native App: Get frames for a specific festival/category/custom post
            Route::get('/get-frames', 'HomeApi@getFrames');
            Route::get('/templates/batch', 'HomeApi@batchTemplates')->middleware('throttle');

            // Festival AI: authenticated with the user's existing API token.
            Route::get('/festival-ai/options', [\App\Http\Controllers\Api\FestivalAiGenerationController::class, 'options']);
            Route::post('/festival-ai/generations', [\App\Http\Controllers\Api\FestivalAiGenerationController::class, 'create'])->middleware('throttle:10,1');
            Route::get('/festival-ai/generations', [\App\Http\Controllers\Api\FestivalAiGenerationController::class, 'history']);
            Route::get('/festival-ai/generations/{festivalAiGeneration}', [\App\Http\Controllers\Api\FestivalAiGenerationController::class, 'show']);

            // Business Post AI is a separate Custom Post journey. It shares
            // one-image credits, never a frame/template render contract.
            Route::get('/business-ai/options', [\App\Http\Controllers\Api\BusinessAiGenerationController::class, 'options']);
            Route::post('/business-ai/content-preview', [\App\Http\Controllers\Api\BusinessAiGenerationController::class, 'preview'])->middleware('throttle:20,1');
            Route::post('/business-ai/generations', [\App\Http\Controllers\Api\BusinessAiGenerationController::class, 'create'])->middleware('throttle:10,1');
            Route::get('/business-ai/generations', [\App\Http\Controllers\Api\BusinessAiGenerationController::class, 'history']);
            Route::get('/business-ai/generations/{businessAiGeneration}', [\App\Http\Controllers\Api\BusinessAiGenerationController::class, 'show']);

            // AI Editable V1 is a separate, frame-free document contract.
            Route::get('/ai-editable/v1/documents/{publicId}', [\App\Http\Controllers\Api\AiEditableDocumentController::class, 'show']);
            Route::post('/ai-editable/v1/documents', [\App\Http\Controllers\Api\AiEditableDocumentController::class, 'create'])->middleware('throttle:10,1');
            Route::post('/ai-editable/v1/documents/{publicId}/save', [\App\Http\Controllers\Api\AiEditableDocumentController::class, 'save'])->middleware('throttle:30,1');

            // Setup Wizard Endpoints
            Route::post('/setup-wizard/status', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'status']);
            Route::post('/setup-wizard/analyze', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'analyze']);
            Route::post('/setup-wizard/import-columns', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'importColumns']);
            Route::post('/setup-wizard/extract-products', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'extractProducts']);
            Route::post('/setup-wizard/import-products', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'importProducts']);
            Route::post('/setup-wizard/reset', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'reset']);

            // Native Products Management
            Route::post('/products/list', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'getProducts']);
            Route::post('/products/create', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'createProduct']);
            Route::post('/products/extract-from-image', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'extractFromImage']);
            Route::post('/products/bulk-create', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'bulkCreateProducts']);
            Route::post('/products/{id}/update', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'updateProduct']);
            Route::post('/products/{id}/delete', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'deleteProduct']);

            // Product Categories Management
            Route::post('/products/categories/list', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'getCategoryList']);
            Route::post('/products/categories/add', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'addCategory']);
            Route::post('/products/categories/update', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'updateCategory']);
            Route::post('/products/categories/delete', [\App\Http\Controllers\Api\SetupWizardApiController::class, 'deleteCategory']);

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

            // Ad Configuration Endpoint â€” serves all AdMob/network settings to Flutter
            Route::get('/ad-config', 'HomeApi@getAdConfig');

            // Notifications
            Route::get('/notifications', 'HomeApi@getNotifications');

            // AI Knowledge Base Endpoints (RAG)
            Route::get('/faqs', 'HomeApi@getFaqs');
            Route::post('/knowledge-base/ingest', [\App\Http\Controllers\KnowledgeBaseController::class, 'ingest']);
            Route::post('/knowledge-base/search', [\App\Http\Controllers\KnowledgeBaseController::class, 'search']);

            // AI Customer Support Chat Endpoints
            Route::get('/tickets', [\App\Http\Controllers\Api\AiChatController::class, 'getTickets']);
            Route::post('/ai-chat/send', [\App\Http\Controllers\Api\AiChatController::class, 'sendMessage']);
            Route::post('/ai-chat/history', [\App\Http\Controllers\Api\AiChatController::class, 'getHistory']);
            Route::post('/ai-chat/close', [\App\Http\Controllers\Api\AiChatController::class, 'closeTicket']);

            // Partner System
            Route::post('/get-partner-dashboard', 'HomeApi@getPartnerDashboard');
            Route::post('/partner-withdraw-request', 'HomeApi@partnerWithdrawRequest');
        });

Route::middleware('mobile.auth')->post('/user', function (Request $request) {
    return $request->user();
});

// Read-only admin analytics for the external MCP server. This uses a dedicated
// Sanctum ability and configured owner email, never a browser-admin session.
Route::prefix('admin/mcp')
    ->middleware(['throttle:60,1', 'mcp.analytics'])
    ->group(function () {
        Route::get('overview', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'overview']);
        Route::get('installs', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'installs']);
        Route::get('sales', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'sales']);
        Route::get('ads', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'adRevenue']);
        Route::get('tickets', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'tickets']);
        Route::get('templates', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'templates']);
        Route::get('reviews', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'reviews']);
        Route::get('users/search', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'searchUsers']);
        Route::get('users/{userId}', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'userDetails'])->whereNumber('userId');
        Route::get('users/{userId}/activity', [\App\Http\Controllers\Api\AdminMcpAnalyticsController::class, 'userActivity'])->whereNumber('userId');
    });

// Task 12: Dunning Webhook
Route::post('webhooks/payment/failed', 'Api\PaymentWebhookController@handle');


// Phase 3 Remaining Endpoints
Route::namespace('Api')->middleware(['throttle'])->group(function () {
    Route::post('onboarding/status', 'UserJourneyController@onboardingStatus');
    Route::post('onboarding/step', 'UserJourneyController@completeStep');
    Route::post('feedback/check-eligibility', 'UserJourneyController@checkEligibility');
    Route::post('feedback/submit', 'UserJourneyController@submitFeedback');
});

// Greeting API Endpoints
Route::namespace('Api')->group(function () {
    Route::any('get_greeting_categories', 'GreetingApiController@categories');
    Route::any('get_greetings_by_category', 'GreetingApiController@get_greetings_by_category');
});

// Editor routes moved to admin.php


Route::get('editor/stickers', 'Api\EditorDataController@getStickers');

