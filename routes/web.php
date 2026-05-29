<?php

use Illuminate\Support\Facades\Route;


// Client App Routes (Protected)
Route::group(['middleware' => ['auth']], function () {
    Route::get('/dashboard', 'MainController@index')->name('home');
    Route::get('/custom', 'MainController@custom')->name('custom');
    Route::get('/business', 'MainController@business')->name('business');
    Route::get('/notifications', 'MainController@notifications')->name('notifications');
    Route::get('/support', 'MainController@support')->name('support');
    Route::get('/faqs', 'MainController@faqs')->name('faqs');
    Route::post('/update-preferred-languages', 'MainController@updatePreferredLanguages')->name('update.preferred_languages');
    Route::get('/business/edit', 'MainController@business_edit')->name('client.business.edit');
    Route::post('/business/update', 'MainController@business_update')->name('client.business.update');
    Route::get('/aitrends', 'MainController@aitrends')->name('aitrends');
    Route::get('/more', 'MainController@more')->name('more');
    Route::get('/festival/{id}', 'MainController@festival_details')->name('festival.details');
    Route::get('/festival/edit/{id}', 'MainController@festival_edit')->name('festival.edit');
    Route::get('/festivals-by-date', 'MainController@getFestivalsByDate')->name('festivals.by.date');

    // Universal Details & Edit
    Route::get('/debug/frame-overlay', function() {
        return view('client.debug_frame_overlay');
    });
    Route::get('/debug/get-frame-config/{id}', function($id) {
        $pf = \App\Models\PosterMaker::find($id);
        if (!$pf) return response()->json(['error' => 'Frame not found']);
        
        $zipName = $pf->zip_name ?? '';
        if (!$zipName) return response()->json(['error' => 'No ZIP associated']);

        $jsonDir = base_path('uploads/template/' . $zipName . '/json');
        $config = null;
        if (is_dir($jsonDir)) {
            $jsonFiles = glob($jsonDir . '/*.json');
            if (!empty($jsonFiles)) {
                $config = json_decode(file_get_contents($jsonFiles[0]));
            }
        }

        $skinsDir = base_path('uploads/template/' . $zipName . '/skins');
        $skinFolder = '';
        if (is_dir($skinsDir)) {
            $dirs = array_filter(glob($skinsDir . '/*'), 'is_dir');
            if (!empty($dirs)) {
                $skinFolder = basename(reset($dirs));
            }
        }

        $skinDirUrl = asset('uploads/template/' . $zipName . '/skins/' . $skinFolder) . '/';

        return response()->json([
            'config' => $config,
            'skinDir' => $skinDirUrl
        ]);
    });

    Route::get('/details/{type}/{id}', 'MainController@universal_details')->name('universal.details');
    Route::get('/edit/{type}/{id}', 'MainController@universal_edit')->name('universal.edit');
    Route::get('/edit-post/{id}', 'MainController@post_edit')->name('post.edit');
    Route::get('/general-posts', 'MainController@general_posts_client')->name('general.posts.client');

    // AI Setup Wizard
    Route::get('/setup-wizard', [\App\Http\Controllers\Web\SetupWizardController::class, 'index'])->name('setup.wizard');
    Route::post('/setup-wizard/analyze', [\App\Http\Controllers\Web\SetupWizardController::class, 'analyze'])->name('setup.analyze');
    Route::get('/setup-wizard/download-columns-excel', [\App\Http\Controllers\Web\SetupWizardController::class, 'downloadColumnsExcel'])->name('setup.download-columns');
    Route::post('/setup-wizard/import-columns', [\App\Http\Controllers\Web\SetupWizardController::class, 'importColumns'])->name('setup.import.columns');
    Route::post('/setup-wizard/extract-products', [\App\Http\Controllers\Web\SetupWizardController::class, 'extractProducts'])->name('setup.extract.products');
    Route::post('/setup-wizard/import-products', [\App\Http\Controllers\Web\SetupWizardController::class, 'importProducts'])->name('setup.import.products');
    Route::get('/setup-wizard/download-products-excel', [\App\Http\Controllers\Web\SetupWizardController::class, 'downloadProductsExcel'])->name('setup.download-products');
    Route::post('/setup-wizard/complete', [\App\Http\Controllers\Web\SetupWizardController::class, 'complete'])->name('setup.complete');
    Route::post('/setup-wizard/reset', [\App\Http\Controllers\Web\SetupWizardController::class, 'reset'])->name('setup.reset');

    // Catalogue Columns
    Route::get('/catalogue-columns', 'CatalogueController@columnsIndex')->name('catalogue.columns');
    Route::post('/catalogue-columns', 'CatalogueController@columnStore')->name('catalogue.columns.store');
    Route::put('/catalogue-columns/{id}', 'CatalogueController@columnUpdate');
    Route::delete('/catalogue-columns/{id}', 'CatalogueController@columnDestroy');
    Route::post('/catalogue-columns/reorder', 'CatalogueController@columnReorder')->name('catalogue.columns.reorder');
    Route::post('/catalogue-columns/{id}/toggle', 'CatalogueController@columnToggle');

    // Products
    Route::get('/products', 'CatalogueController@productsIndex')->name('products');
    Route::post('/products', 'CatalogueController@productStore')->name('products.store');
    Route::put('/products/{id}', 'CatalogueController@productUpdate');
    Route::delete('/products/{id}', 'CatalogueController@productDestroy');
    // AI Magic Cloner (Web Session)
    Route::post('/magic-cloner/analyze', 'MagicClonerWebController@analyze')->name('magic_cloner.analyze');

    // Lazy AI Generation for Custom Frames (Just-In-Time)
    Route::post('/generate-frame-content', 'MainController@generateFrameContent')->name('generate.frame.content');

    // Favorite Frames
    Route::post('/toggle-favorite-frame', 'Api\FrameApiController@toggleFavorite')->name('toggle.favorite.frame');

    // My Products Panel (Editor)
    Route::get('/my-product-images', 'MainController@getProductImages')->name('my.product.images');
    Route::post('/save-product-selection', 'MainController@saveProductSelection')->name('save.product.selection');

    // Photoroom Background Removal API Endpoint (Web Session)
    Route::post('/remove-background', [\App\Http\Controllers\Api\BackgroundRemovalController::class, 'removeBackground'])->name('remove-background');

    // Partner Portal
    Route::prefix('partner')->name('partner.')->group(function () {
        Route::get('/toolkit', 'Partner\ToolkitController@index')->name('toolkit');
        Route::post('/toolkit/generate', 'Partner\ToolkitController@generate')->name('toolkit.generate');
        Route::get('/profile', 'Partner\ProfileController@index')->name('profile');
        Route::post('/profile', 'Partner\ProfileController@update')->name('profile.update');
    });
});

// Shared UI routes
Route::get('/client/get-sub-categories/{category_id}', 'MainController@getSubCategories')->name('client.get_sub_categories');
Route::get('/webview-login', 'ClientAuthController@webviewLogin')->name('webview.login');

// Client Auth Routes (Guest)
Route::group(['middleware' => ['guest']], function () {
    Route::get('/login', 'ClientAuthController@showLoginForm')->name('client.login');
    Route::post('/login', 'ClientAuthController@login')->name('client.login.post');
    Route::get('/register', 'ClientAuthController@showRegistrationForm')->name('client.register');
    Route::post('/register', 'ClientAuthController@register')->name('client.register.post');
});
Route::post('/logout', 'ClientAuthController@logout')->name('logout');

// Google OAuth Routes (accessible to both guests and authenticated users)
Route::get('/auth/google', 'ClientAuthController@redirectToGoogle')->name('auth.google');
Route::get('/auth/google/callback', 'ClientAuthController@handleGoogleCallback')->name('auth.google.callback');

// Forgot Password Routes
Route::get('/forgot-password', 'ClientAuthController@showForgotForm')->name('password.forgot');
Route::post('/forgot-password/send-otp', 'ClientAuthController@sendOtp')->name('password.send-otp');
Route::post('/forgot-password/verify-otp', 'ClientAuthController@verifyOtp')->name('password.verify-otp');
Route::post('/forgot-password/update', 'ClientAuthController@updatePassword')->name('password.update');


Route::group(['middleware' => ['canInstall']], function () {
    Route::get("installation", 'HomeController@install')->name('install');
    Route::Post("licence-validation", 'HomeController@installation');
    Route::get("database-setup", 'HomeController@database_setup')->name('database_setup');
    Route::Post("database-setup-post", 'HomeController@database_setup_post');
    Route::get('migration', 'HomeController@migration');
});

Route::group(['middleware' => ['IsInstalled', 'canUpdate']], function () {
    Route::get("update-version", 'HomeController@update_version');
    Route::Post("update-version", 'HomeController@update_version_post');
});

Route::get('licence-details', 'HomeController@licence_details');
Route::get('destroy', 'HomeController@destroy_data');
Route::get('destroydb', 'HomeController@destroy_data_db');
Route::get("privacy-policy", 'HomeController@privacy_policy');
Route::get("refund-policy", 'HomeController@refund_policy');
Route::get("terms-condition", 'HomeController@term_condition');
Route::get('template', 'HomeController@temp');
Route::get('update-all-date', 'HomeController@update_date');
Route::get("account-deletion-policy", 'HomeController@user_account_delete');

Route::get('/invoice/{id}', [App\Http\Controllers\InvoiceController::class, 'show'])->name('invoice.show');

Route::get('upload-all-image-digitalOcean', 'HomeController@upload_image_digitalOcean');

// Marketing Landing Pages
Route::get('/', 'LandingController@home')->name('landing.home');
Route::get('/auth-gate', 'LandingController@authGate')->name('landing.auth_gate');
Route::get('/app-gateway', 'LandingController@appGateway')->name('landing.app_gateway');
Route::get('/about', 'LandingController@about')->name('landing.about');
Route::get('/features', 'LandingController@features')->name('landing.features');
Route::get('/packages', 'LandingController@packages')->name('landing.packages');
Route::get('/reviews', 'LandingController@reviews')->name('landing.reviews');
Route::get('/blogs', 'LandingController@blogs')->name('landing.blogs');
Route::get('/blog/{slug}', 'LandingController@blogDetails')->name('landing.blog_details');
Route::get('/contact', 'LandingController@contact')->name('landing.contact');

// New Public Pages
Route::get('/templates', 'LandingController@templates')->name('landing.templates');
Route::get('/category/{slug}', 'LandingController@category')->name('landing.category');
Route::get('/digital-business-cards', 'LandingController@digitalBusinessCards')->name('landing.digital_business_cards');
Route::get('/logo-maker', 'LandingController@logoMaker')->name('landing.logo_maker');
Route::get('/video-maker', 'LandingController@videoMaker')->name('landing.video_maker');
Route::post('/client-log', function(Illuminate\Http\Request $request) { Illuminate\Support\Facades\Log::info('ClientJS Log: ' . $request->message); return response()->json(['status' => 'ok']); });
