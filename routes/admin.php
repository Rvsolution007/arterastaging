<?php

use Illuminate\Support\Facades\Artisan;

Route::group(['middleware' => ['IsUpdate']], function () {
    Auth::routes(['register' => false]);
});

Route::post('login', 'Auth\LoginController@authenticate')->middleware(['IsInstalled', 'IsUpdate'])->name('admin.login');

Route::
        namespace('Admin')->middleware(['auth', 'admin', 'IsInstalled', 'IsUpdate'])->group(function () {
            Route::post("logout", 'HomeController@logout')->name('admin.logout');
            Route::get('/', 'HomeController@index')->name('admin');

            Route::get("clear-cache", function () {
                Cache::flush();
                Artisan::call('optimize:clear');
                Artisan::call('clear-compiled');

                return back();
            })->middleware(['IsInstalled', 'IsUpdate']);

            Route::get('get-details', 'HomeController@get_details');
            Route::get('sql-database', 'HomeController@database')->middleware(['IsInstalled', 'IsUpdate']);


            //Route::get('members','HomeController@members');
            Route::get('user-profile', 'HomeController@userProfile');
            Route::Post('user-profile', 'HomeController@userProfileUpdate');

            Route::resource('language', 'LanguageController');
            Route::Post('language-status', 'LanguageController@language_status');

            Route::resource('category', 'CategoryController');
            Route::Post('category-status', 'CategoryController@category_status');
            Route::Post('category-feature-status', 'CategoryController@category_feature_status');

            Route::resource('category-post', 'CategoryPostController');
            Route::Post('category-post-status', 'CategoryPostController@category_post_status');
            Route::Post('category-post-type', 'CategoryPostController@category_post_type');
            Route::get('get-category-post', 'CategoryPostController@get_category_post');
            Route::get('category-get/{id}', 'CategoryPostController@category_get');
            Route::Post('category-post-action', 'CategoryPostController@category_post_action');
            Route::Post('category-post-landing', 'CategoryPostController@category_post_landing');
            Route::Post('category-post-ai', 'CategoryPostController@category_post_ai');

            Route::resource('festivals', 'FestivalsController');
            Route::Post('festivals-status', 'FestivalsController@festivals_status');
            Route::Post('festivals-feature-status', 'FestivalsController@festivals_feature_status');
            Route::get('festivals-search', 'FestivalsController@festivals_search');
            Route::Post('festivals-action', 'FestivalsController@festivals_action');

            Route::resource('festivals-post', 'FestivalsPostController');
            Route::Post('festivals-post-status', 'FestivalsPostController@festivals_post_status');
            Route::Post('festivals-post-type', 'FestivalsPostController@festivals_post_type');
            Route::get('festival/{id}', 'FestivalsPostController@festival_filter');
            Route::Post('festivals-post-action', 'FestivalsPostController@festivals_post_action');
            Route::Post('festivals-post-landing', 'FestivalsPostController@festivals_post_landing');
            Route::Post('festivals-post-ai', 'FestivalsPostController@festivals_post_ai');

            Route::resource('custom-post-category', 'CustomPostController');
            Route::Post('custom-post-category-status', 'CustomPostController@custom_post_status');
            Route::Post('custom-feature-status', 'CustomPostController@custom_feature_status');

            Route::resource('custom-post-frame', 'CustomPostFrameController');
            Route::Post('custom-post-frame-status', 'CustomPostFrameController@custom_post_frame_status');
            Route::Post('custom-post-frame-type', 'CustomPostFrameController@custom_post_frame_type');
            Route::get('custom-post-get/{id}', 'CustomPostFrameController@custom_post_get');
            Route::Post('custom-post-frame-action', 'CustomPostFrameController@custom_post_frame_action');
            Route::Post('custom-post-frame-landing', 'CustomPostFrameController@custom_post_frame_landing');

            Route::resource('greeting-category', 'GreetingCategoryController');
            Route::Post('greeting-category-status', 'GreetingCategoryController@greeting_category_status');
            
            Route::resource('greeting', 'GreetingController');
            Route::Post('greeting-status', 'GreetingController@greeting_status');
            Route::Post('greeting-type', 'GreetingController@greeting_type');
            Route::get('greeting-get/{id}', 'GreetingController@greeting_get');
            Route::Post('greeting-action', 'GreetingController@greeting_action');

            Route::post('business-category/bulk-delete', 'BusinessCategoryController@bulkDelete');
            Route::get('business-category/export', 'BusinessCategoryController@export')->name('business-category.export');
            Route::post('business-category/import', 'BusinessCategoryController@import')->name('business-category.import');
            Route::post('business-category/search', 'BusinessCategoryController@search');
            Route::Post('business-category-status', 'BusinessCategoryController@business_category_status');
            Route::resource('business-category', 'BusinessCategoryController');
            
            Route::post('product-type/bulk-delete', 'ProductTypeController@bulkDelete');
            Route::post('product-type/search', 'ProductTypeController@search');
            Route::get('product-type/export', 'ProductTypeController@export')->name('product-type.export');
            Route::post('product-type/import', 'ProductTypeController@import')->name('product-type.import');
            Route::resource('product-type', 'ProductTypeController');
            
            Route::post('brand-status', 'BrandController@brand_status');
            Route::post('brand/bulk-delete', 'BrandController@bulkDelete');
            Route::get('brand/export', 'BrandController@export')->name('brand.export');
            Route::post('brand/import', 'BrandController@import')->name('brand.import');
            Route::post('brand/search', 'BrandController@search');
            Route::resource('brand', 'BrandController');
            
            Route::resource('category-background-image', 'CategoryBackgroundImageController');
            
            Route::get('business-sub-category/export', 'BusinessSubCategoryController@export')->name('business-sub-category.export');
            Route::post('business-sub-category/import', 'BusinessSubCategoryController@import')->name('business-sub-category.import');
            Route::post('business-sub-category-sort-order', 'BusinessSubCategoryController@updateSortOrder');
            Route::post('business-sub-category/bulk-delete', 'BusinessSubCategoryController@bulkDelete');
            Route::post('business-sub-category/search', 'BusinessSubCategoryController@search');
            Route::Post('business-sub-category-status', 'BusinessSubCategoryController@business_sub_category_status');
            Route::resource('business-sub-category', 'BusinessSubCategoryController');
            
            Route::Post('business-type-status', 'BusinessTypeController@business_type_status');
            Route::post('business-type/bulk-delete', 'BusinessTypeController@bulkDelete');
            Route::get('business-type/export', 'BusinessTypeController@export')->name('business-type.export');
            Route::post('business-type/import', 'BusinessTypeController@import')->name('business-type.import');
            Route::post('business-type/search', 'BusinessTypeController@search');
            Route::get('business-type/get-sub-categories', 'BusinessTypeController@getSubCategories');
            Route::resource('business-type', 'BusinessTypeController');
            
            Route::get('business-product/get-sub-categories', 'BusinessProductController@getSubCategories');
            Route::get('business-product/get-business-types', 'BusinessProductController@getBusinessTypes');
            Route::Post('business-product-status', 'BusinessProductController@business_product_status');
            Route::post('business-product/bulk-delete', 'BusinessProductController@bulkDelete');
            Route::get('business-product/export', 'BusinessProductController@export')->name('business-product.export');
            Route::post('business-product/import', 'BusinessProductController@import')->name('business-product.import');
            Route::post('business-product/search', 'BusinessProductController@search');
            Route::resource('business-product', 'BusinessProductController');
            
            Route::get('custom-product-request', 'CustomProductRequestController@index')->name('custom-product-request.index');
            Route::post('custom-product-request/{id}/resolve', 'CustomProductRequestController@resolve')->name('custom-product-request.resolve');
            
            Route::get('get-business-sub-category', 'BusinessFrameController@get_business_sub_category');
            Route::resource('custom-post', 'BusinessFrameController');
            Route::Post('custom-post-status-bf', 'BusinessFrameController@business_frame_status');
            Route::Post('custom-post-type', 'BusinessFrameController@business_frame_type');
            Route::get('custom-post-bcat-get/{id}', 'BusinessFrameController@business_category_get');
            Route::post('custom-frame-purpose-create', 'BusinessFrameController@createCustomFramePurpose');
            Route::post('custom-frame-purpose', 'BusinessFrameController@storeCustomFramePurpose');
            Route::post('custom-frame-purpose-remove-ai', 'BusinessFrameController@removeCustomFramePurposeAi');
            Route::put('custom-frame-purpose/{id}', 'BusinessFrameController@updateCustomFramePurpose');
            Route::delete('custom-frame-purpose/{id}', 'BusinessFrameController@deleteCustomFramePurpose');
            Route::post('custom-frame-image-type', 'BusinessFrameController@storeCustomFrameImageType');
            Route::post('business-custom-frame-zip', 'BusinessFrameController@storeBusinessCustomFrame');
            Route::put('business-custom-frame-zip/{id}', 'BusinessFrameController@updateBusinessCustomFrame');
            Route::delete('business-custom-frame-zip/{id}', 'BusinessFrameController@deleteBusinessCustomFrame');
            Route::post('business-custom-frame-landing', 'BusinessFrameController@business_custom_frame_landing');
            Route::Post('custom-post-action', 'BusinessFrameController@business_frame_action');
            Route::get('custom-post-export', 'BusinessFrameController@exportTemplates')->name('custom-post.export');
            Route::post('custom-post-import', 'BusinessFrameController@importTemplates')->name('custom-post.import');

            Route::resource('general-post', 'GeneralPostController');
            Route::Post('general-post-status', 'GeneralPostController@general_post_status');
            Route::Post('general-post-type', 'GeneralPostController@general_post_type');
            Route::get('get-business-sub-category', 'GeneralPostController@get_business_sub_category');
            Route::get('business-category-get-general/{id}', 'GeneralPostController@business_category_get');
            Route::Post('general-post-action', 'GeneralPostController@general_post_action');
            Route::Post('general-post-subcategory-image', 'GeneralPostController@update_subcategory_images');

            Route::resource('zip-file-manager', 'ZipFileManagerController');

            Route::resource('sticker-category', 'StickerCategoryController');
            Route::Post('sticker-category-status', 'StickerCategoryController@sticker_category_status');
            Route::resource('sticker', 'StickerController');
            Route::post('sticker/generate-ai', 'StickerController@generateAi');
            Route::Post('sticker-status', 'StickerController@sticker_status');
            Route::get('sticker-category-get/{id}', 'StickerController@sticker_category_get');
            Route::Post('sticker-action', 'StickerController@sticker_action');

            Route::resource('inquiry', 'InquiryController');

            Route::get('Frame/version-control', 'PosterMakerController@versionControl')->name('admin.poster_maker.version_control');
            Route::post('Frame/bulk-migrate-version', 'PosterMakerController@bulkMigrateVersion')->name('admin.poster_maker.bulk_migrate');
            Route::post('Frame/auto-compensate', 'PosterMakerController@autoCompensate')->name('admin.poster_maker.auto_compensate');

            Route::get('regression-test-log', 'RegressionTestController@index')->name('admin.regression_tests.index');
            Route::post('regression-test-run', 'RegressionTestController@runTests')->name('admin.regression_tests.run');
            Route::get('benchmark-frames', 'RegressionTestController@benchmarks')->name('admin.regression_tests.benchmarks');
            Route::post('benchmark-toggle', 'RegressionTestController@toggleBenchmark')->name('admin.regression_tests.toggle_benchmark');
            Route::resource('Frame', 'PosterMakerController');
            Route::post('Frame/bulk-delete', 'PosterMakerController@bulkDelete')->name('admin.poster_maker.bulk_delete');
            Route::post('Frame/duplicate', 'PosterMakerController@duplicate')->name('admin.poster_maker.duplicate');
            Route::post('Frame-frame-type', 'PosterMakerController@poster_maker_frame_type');
            Route::get('Frame-export', 'PosterMakerController@exportFrames')->name('admin.poster_maker.export');
            Route::post('Frame-import', 'PosterMakerController@importFrames')->name('admin.poster_maker.import');
            Route::resource('Frame-category', 'PosterCategoryController');
            Route::Post('Frame-category-status', 'PosterCategoryController@poster_category_status');

            Route::get('referral-system', 'ReferralSystemController@referral_system');
            Route::post('referral-system', 'ReferralSystemController@post_referral_system');
            Route::get('withdraw-request', 'ReferralSystemController@withdraw_request');
            Route::post('withdraw-request', 'ReferralSystemController@post_withdraw_request');
            Route::get('partner-leaderboard', 'ReferralSystemController@leaderboard')->name('admin.partner_leaderboard');

            Route::resource('video', 'VideoController');
            Route::Post('video-status', 'VideoController@video_status');
            Route::get('video-list/{type}', 'VideoController@video_list');
            Route::get('video-list/{type}/{id}', 'VideoController@video_list_id');
            Route::Post('video-type', 'VideoController@video_type');
            Route::Post('video-action', 'VideoController@video_action');

            Route::resource('news', 'NewsController');
            Route::resource('story', 'StoryController');
            Route::Post('story-status', 'StoryController@story_status');
            Route::Post('story-sort-order', 'StoryController@updateSortOrder');

            Route::resource('user', 'UserController');
            Route::post('user-bulk-delete', 'UserController@bulkDelete')->name('admin.user.bulk_delete');
            Route::Post('user-status', 'UserController@user_status');
            Route::Post('subscription-update', 'UserController@subscription_update');
            Route::get('user-get-plan', 'UserController@get_plan');
            Route::get('user-activities', 'UserActivityController@index')->name('admin.user_activities');
            Route::get('reported-errors', 'ClientErrorController@index')->name('admin.reported_errors');
            Route::post('reported-errors/bulk-destroy', 'ClientErrorController@bulk_destroy')->name('admin.reported_errors.bulk_destroy');
            Route::post('reported-errors/bulk-status', 'ClientErrorController@bulkUpdateStatus')->name('admin.reported_errors.bulk_status');
            Route::post('reported-errors/status/{id}', 'ClientErrorController@updateStatus')->name('admin.reported_errors.status');
            Route::delete('reported-errors/{id}', 'ClientErrorController@destroy')->name('admin.reported_errors.destroy');
            Route::post('reported-errors/ai-analyze/{id}', 'ClientErrorController@analyzeWithAi')->name('admin.reported_errors.ai_analyze');
            Route::post('reported-errors/ai-batch-analyze', 'ClientErrorController@batchAnalyze')->name('admin.reported_errors.ai_batch');
            Route::post('reported-errors/toggle-auto-analyze', 'ClientErrorController@toggleAutoAnalyze')->name('admin.reported_errors.toggle_auto');

            Route::resource('business', 'BusinessController');
            Route::Post('business-status', 'BusinessController@business_status');
            Route::get('user-business/{id}', 'BusinessController@user_business');

            Route::resource('subscription-plan', 'SubscriptionController');
            Route::Post('subscription-plan-status', 'SubscriptionController@subscription_status');

            Route::resource('coupon-code', 'CouponCodeController');
            Route::Post('coupon-code-status', 'CouponCodeController@coupon_code_status');

            Route::get('transaction', 'HomeController@transaction');
            Route::post('transaction-delete', 'HomeController@transaction_delete');
            Route::get('payment-completed/{id}', 'HomeController@payment_completed');
            Route::get('notification', 'HomeController@notification')->name('admin.notification');
            Route::get('notification-list', 'HomeController@notification_list')->name('admin.notification_list');
            Route::post('notification', 'HomeController@post_notification');
            Route::post('notification-delete', 'HomeController@notification_delete')->name('admin.notification_delete');
            Route::post('today-event-notification', 'HomeController@today_event_notification');

            Route::resource('whatsapp-message', 'WhatsappMessageController');
            Route::post('send-whatsapp-msg', 'WhatsappMessageController@send_whatsapp_msg');
            Route::post('send-whatsapp-msg-user', 'WhatsappMessageController@send_whatsapp_msg_user');

            Route::resource('custom-frame', 'CustomFrameController');
            Route::Post('custom-frame-status', 'CustomFrameController@custom_frame_status');

            Route::resource('offer', 'OfferController');
            Route::Post('offer-status', 'OfferController@offer_status');

            Route::resource('business-card', 'BusinessCardController');
            Route::Post('business-card-status', 'BusinessCardController@business_card_status');

            Route::resource('entry', 'EntryController');
            Route::resource('subject', 'SubjectController');

            Route::resource('backup', 'BackupController');
            Route::get('backup/download/{name}', 'BackupController@download');

            Route::get('destroy', 'SettingController@destroy_data');
            Route::get('settings', 'SettingController@setting');
            Route::post('app-setting', 'SettingController@app_setting');
            Route::post('email-setting', 'SettingController@email_setting');
            Route::post('notification-setting', 'SettingController@notification_setting');
            Route::post('payment-setting', 'SettingController@payment_setting');
            Route::post('storage-setting', 'SettingController@storage_setting');
            Route::post('api-setting', 'SettingController@api_setting');
            Route::post('whatsapp-setting', 'SettingController@whatsapp_setting');
            Route::post('whatsapp-generate-qr', 'SettingController@generateWhatsappQr');
            Route::post('app-update-setting', 'SettingController@app_update_setting');
            Route::post('other-setting', 'SettingController@other_setting');
            Route::post('ads-setting', 'SettingController@ads_setting');
            Route::post('whatsapp-contact', 'SettingController@whatsapp_contact');
            Route::post('ai-setting', 'SettingController@ai_setting');
            Route::get('check-ai-connection', 'SettingController@check_ai_connection');
            Route::post('ai-playground-chat', 'SettingController@ai_playground_chat');
            
            // AI Token Analytics
            Route::get('ai-analytics', 'AiAnalyticsController@index')->name('admin.ai_analytics');
            Route::get('user-performance', 'UserPerformanceController@index')->name('admin.user_performance');
            Route::get('user-performance/details', 'UserPerformanceController@details')->name('admin.user_performance.details');
            
            // AI Generation Monitor & Debugger
            Route::get('ai-monitor', 'AiMonitorController@index')->name('admin.ai_monitor');
            Route::get('ai-monitor/batch/{id}', 'AiMonitorController@batchLogs')->name('admin.ai_monitor.batch');
            Route::post('ai-monitor/playground', 'AiMonitorController@playground')->name('admin.ai_monitor.playground');
            Route::get('ai-monitor/batch-status/{id}', 'AiMonitorController@batchStatus')->name('admin.ai_monitor.batch_status');
            
            // Magic Cloner Admin Setup

            Route::resource('post-purpose', 'PostPurposeController');
            Route::post('post-purpose-status', 'PostPurposeController@post_purpose_status');


            Route::post('test-image-digitalOcean', 'SettingController@test_image_digitalOcean');
            Route::get('get-zip-library', 'ZipFileManagerController@getZipLibrary');
            Route::post('ajax-zip-upload', 'ZipFileManagerController@ajaxStore');
            // Route::post('check-credentials-digitalOcean','SettingController@check_credentials_digitalOcean');
            // Route::get('move-local-to-digitalOcean','SettingController@move_local_to_digitalOcean');
        
            Route::resource('roles', 'UserAccessController');
            Route::get('create-permission', 'UserAccessController@create_permission');
            Route::get('generat-code', 'UserController@generat_code');

            // Phase 4 & 5 Missing Routes
            
            Route::get('blogs', 'BlogController@index')->name('admin.blogs');
            Route::get('blogs/{id}/edit', 'BlogController@edit')->name('admin.blogs.edit');
            Route::put('blogs/{id}', 'BlogController@update')->name('admin.blogs.update');
            Route::delete('blogs/{id}', 'BlogController@destroy')->name('admin.blogs.destroy');

            // Home Banners
            Route::get('home-banners', 'HomeBannerController@index')->name('admin.home_banners.index');
            Route::post('home-banners', 'HomeBannerController@store')->name('admin.home_banners.store');
            Route::post('home-banners/sort', 'HomeBannerController@updateSort')->name('admin.home_banners.sort');
            Route::delete('home-banners/{id}', 'HomeBannerController@destroy')->name('admin.home_banners.destroy');
            Route::get('auto-notification', 'MarketingSettingsController@index')->name('admin.auto_notification');
            Route::get('manual-notification', 'AiSmartCampaignController@index')->name('admin.manual_notification');
            Route::post('manual-notification/generate', 'AiSmartCampaignController@generateCopy')->name('admin.manual_notification.generate');
            Route::post('manual-notification/send', 'AiSmartCampaignController@sendCampaign')->name('admin.manual_notification.send');
            Route::post('manual-notification/bulk-delete', 'AiSmartCampaignController@bulkDelete')->name('admin.manual_notification.bulk_delete');
            
            Route::get('tickets', 'TicketController@index')->name('admin.tickets');
            Route::get('tickets/{id}', 'TicketController@show')->name('admin.tickets.show');
            Route::post('tickets/{id}/reply', 'TicketController@reply')->name('admin.tickets.reply');
            Route::post('tickets/{id}/status', 'TicketController@updateStatus')->name('admin.tickets.updateStatus');

            // Support & Ops - Knowledge Base (AI Training)
            Route::get('knowledge-base', 'AdminKnowledgeBaseController@index')->name('admin.knowledge_base');
            Route::get('knowledge-base/export', 'AdminKnowledgeBaseController@exportCsv')->name('admin.knowledge_base.export');
            Route::post('knowledge-base/import', 'AdminKnowledgeBaseController@importCsv')->name('admin.knowledge_base.import');
            Route::post('knowledge-base/update-context', 'AdminKnowledgeBaseController@updateContext')->name('admin.knowledge_base.update_context');
            Route::post('knowledge-base/auto-sync-context', 'AdminKnowledgeBaseController@autoSyncUiContext')->name('admin.knowledge_base.auto_sync_context');
            Route::post('knowledge-base/ai-category', 'AdminKnowledgeBaseController@generateAiCategory')->name('admin.knowledge_base.ai_category');
            Route::post('knowledge-base/ai-question', 'AdminKnowledgeBaseController@generateAiQuestion')->name('admin.knowledge_base.ai_question');
            Route::get('knowledge-base/create', 'AdminKnowledgeBaseController@create')->name('admin.knowledge_base.create');
            Route::post('knowledge-base', 'AdminKnowledgeBaseController@store')->name('admin.knowledge_base.store');
            Route::get('knowledge-base/{id}/edit', 'AdminKnowledgeBaseController@edit')->name('admin.knowledge_base.edit');
            Route::post('knowledge-base/{id}', 'AdminKnowledgeBaseController@update')->name('admin.knowledge_base.update');
            Route::get('knowledge-base/{id}/delete', 'AdminKnowledgeBaseController@destroy')->name('admin.knowledge_base.delete');

            Route::get('churn-analytics', 'ChurnController@index')->name('admin.churn_analytics');
            Route::post('churn/generate-strategy/{id}', 'ChurnController@generateStrategy')->name('admin.churn.generate-strategy');
            Route::post('churn/send-mail/{id}', 'ChurnController@sendMail')->name('admin.churn.send-mail');
            Route::post('churn/send-strategy-whatsapp/{id}', 'ChurnController@sendStrategyWhatsapp')->name('admin.churn.send-strategy-whatsapp');
            Route::post('churn/send-notification/{id}', 'ChurnController@sendNotification')->name('admin.churn.send-notification');
            Route::post('churn/send-dunning-email/{id}', 'ChurnController@sendDunningEmail');
            Route::post('churn/send-dunning-whatsapp/{id}', 'ChurnController@sendDunningWhatsapp');
            Route::post('churn/trigger-discovery/{featureName}', 'ChurnController@triggerDiscovery');

            Route::get('app-language/export', 'AppLanguageController@export')->name('app-language.export');
            Route::post('app-language/import', 'AppLanguageController@import')->name('app-language.import');
            Route::resource('app-language', 'AppLanguageController');
            Route::get('app-language', 'AppLanguageController@index')->name('app-language.index');

            // Phase 7 Design Challenges
            Route::get('challenges', 'DesignChallengeController@index')->name('admin.challenges');
            Route::post('challenges', 'DesignChallengeController@store')->name('admin.challenges.store');
            Route::post('challenges/{id}/update', 'DesignChallengeController@update')->name('admin.challenges.update');
            Route::get('challenges/{id}/toggle', 'DesignChallengeController@toggleStatus')->name('admin.challenges.toggle');
            Route::delete('challenges/{id}/destroy', 'DesignChallengeController@destroy')->name('admin.challenges.destroy');

            // Phase 8 God View
            Route::get('god-view', 'GodViewController@index')->name('admin.god_view');
            Route::get('god-view/resolve/{id}', 'GodViewController@resolveAlert')->name('admin.god_view.resolve');


            Route::post('churn/generate-lead-strategy/{id}', 'ChurnController@generateLeadStrategy')->name('admin.churn.generate-lead-strategy');

            // Documentation
            Route::get('documentation', 'DocumentationController@index')->name('admin.documentation');

            // Template Builder
            Route::get('template-builder', 'TemplateBuilderController@index')->name('template_builder.index');
            Route::get('template-builder/stickers', 'TemplateBuilderController@getStickers')->name('template_builder.stickers');
            Route::get('template-builder/load-zip/{id}', 'TemplateBuilderController@loadZip')->name('template_builder.load_zip');
            Route::get('template-builder/load-frame-zip/{id}', 'TemplateBuilderController@loadFrameZip')->name('template_builder.load_frame_zip');
            Route::post('template-builder/parse-zip', 'TemplateBuilderController@parseZip')->name('template_builder.parse_zip');
            Route::post('template-builder/save', 'TemplateBuilderController@save')->name('template_builder.save');
            Route::post('template-builder/save-frame', 'TemplateBuilderController@saveFrame')->name('template_builder.save_frame');

            // Template Builder Editor Assets/Fonts
            Route::get('editor/fonts', function() {
                $fonts = \App\Models\Font::where('status', 1)->get()->map(function($f) {
                    return [
                        'name' => $f->name,
                        'family' => $f->name,
                        'file_path' => $f->file_path ? asset('uploads/fonts/' . basename($f->file_path)) : null
                    ];
                })->toArray();
                $fallbacks = ['Arial', 'Times New Roman', 'Courier New', 'Verdana'];
                $fallbackFonts = array_map(function($f) {
                    return ['name' => $f, 'family' => $f, 'file_path' => null];
                }, $fallbacks);
                
                // Remove duplicates by name
                $merged = array_merge($fallbackFonts, $fonts);
                $unique = [];
                foreach ($merged as $item) {
                    if (!isset($unique[$item['name']])) {
                        $unique[$item['name']] = $item;
                    }
                }

                return response()->json([
                    'success' => true,
                    'data' => array_values($unique)
                ]);
            });
            Route::get('editor/assets', function() {
                return response()->json(['success' => true, 'data' => []]);
            });


            // Fonts Manager
            Route::get('fonts', 'FontController@index')->name('admin.fonts.index');
            Route::get('fonts/export', 'FontController@export')->name('admin.fonts.export');
            Route::post('fonts/import', 'FontController@import')->name('admin.fonts.import');
            Route::get('fonts/create', 'FontController@create')->name('admin.fonts.create');
            Route::post('fonts', 'FontController@store')->name('admin.fonts.store');
            Route::put('fonts/{id}', 'FontController@update')->name('admin.fonts.update');
            Route::delete('fonts/{id}', 'FontController@destroy')->name('admin.fonts.destroy');
        });
