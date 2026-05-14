<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance Optimization: Add database indexes on frequently queried columns.
 * 
 * This migration ONLY adds indexes — no data is modified, no columns are changed.
 * Fully reversible via the down() method.
 */
return new class extends Migration
{
    public function up()
    {
        // Festivals table — filtered by status + date on every home/festival API
        if (Schema::hasTable('festivals')) {
            Schema::table('festivals', function (Blueprint $table) {
                $table->index(['status', 'festivals_date'], 'idx_festivals_status_date');
                $table->index('activation_date', 'idx_festivals_activation_date');
            });
        }

        // Festival Posts — joined/filtered by festivals_id + status on every post listing
        if (Schema::hasTable('festivals_post')) {
            Schema::table('festivals_post', function (Blueprint $table) {
                $table->index(['festivals_id', 'status'], 'idx_festpost_fid_status');
                $table->index('language_id', 'idx_festpost_language');
            });
        }

        // Categories — filtered by status on every listing
        if (Schema::hasTable('categories')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('status', 'idx_categories_status');
            });
        }

        // Category Posts — joined/filtered by category_id + status
        if (Schema::hasTable('category_post')) {
            Schema::table('category_post', function (Blueprint $table) {
                $table->index(['category_id', 'status'], 'idx_catpost_cid_status');
                $table->index('language_id', 'idx_catpost_language');
            });
        }

        // Business — filtered by user_id + status + is_default on every business API
        if (Schema::hasTable('business')) {
            Schema::table('business', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'is_default'], 'idx_business_uid_status_default');
            });
        }

        // Business Custom Frames — heavy queries in customPost(), getCustomFrame(), getBusinessFrame()
        if (Schema::hasTable('business_custom_frames')) {
            Schema::table('business_custom_frames', function (Blueprint $table) {
                $table->index(['custom_frame_purpose_id', 'status'], 'idx_bcf_purpose_status');
            });
        }

        // User Custom Frame Contents — AI content lookup per user per frame
        if (Schema::hasTable('user_custom_frame_contents')) {
            Schema::table('user_custom_frame_contents', function (Blueprint $table) {
                $table->index(['user_id', 'business_custom_frame_id'], 'idx_ucfc_uid_frameid');
            });
        }

        // Videos — queried by type + foreign key in loops
        if (Schema::hasTable('videos')) {
            Schema::table('videos', function (Blueprint $table) {
                $table->index(['type', 'festival_id', 'status'], 'idx_videos_type_festival');
                $table->index(['type', 'category_id', 'status'], 'idx_videos_type_category');
                $table->index(['type', 'business_category_id', 'status'], 'idx_videos_type_business');
            });
        }

        // Stickers — filtered by category + status
        if (Schema::hasTable('stickers')) {
            Schema::table('stickers', function (Blueprint $table) {
                $table->index(['sticker_category_id', 'status'], 'idx_stickers_catid_status');
            });
        }

        // Sticker Categories — filtered by status
        if (Schema::hasTable('sticker_categories')) {
            Schema::table('sticker_categories', function (Blueprint $table) {
                $table->index('status', 'idx_stickercat_status');
            });
        }

        // User Favorite Frames — lookup by user_id
        if (Schema::hasTable('user_favorite_frames')) {
            Schema::table('user_favorite_frames', function (Blueprint $table) {
                $table->index(['user_id', 'frame_identifier'], 'idx_favframes_uid_fid');
            });
        }

        // Products — filtered by status, user_id
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                $table->index('status', 'idx_products_status');
                $table->index('user_id', 'idx_products_userid');
            });
        }

        // Product Categories — filtered by status
        if (Schema::hasTable('product_categories')) {
            Schema::table('product_categories', function (Blueprint $table) {
                $table->index('status', 'idx_prodcat_status');
            });
        }

        // Referral Register — lookup by user_id + referral_code
        if (Schema::hasTable('referral_registers')) {
            Schema::table('referral_registers', function (Blueprint $table) {
                $table->index(['user_id', 'referral_code'], 'idx_referral_uid_code');
            });
        }

        // Coupon Code Store — lookup by code + user_id
        if (Schema::hasTable('coupon_code_stores')) {
            Schema::table('coupon_code_stores', function (Blueprint $table) {
                $table->index(['code', 'user_id'], 'idx_couponstore_code_uid');
            });
        }

        // User Activities — lookup by user_id
        if (Schema::hasTable('user_activities')) {
            Schema::table('user_activities', function (Blueprint $table) {
                $table->index('user_id', 'idx_useract_uid');
            });
        }

        // User Notifications — ordered by created_at
        if (Schema::hasTable('user_notifications')) {
            Schema::table('user_notifications', function (Blueprint $table) {
                $table->index('created_at', 'idx_usernotif_created');
            });
        }

        // Transactions — lookup by user_id
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->index('user_id', 'idx_transactions_uid');
            });
        }

        // Custom Frame Purposes — filtered by status (used as business categories)
        if (Schema::hasTable('custom_frame_purposes')) {
            Schema::table('custom_frame_purposes', function (Blueprint $table) {
                $table->index('status', 'idx_cfpurpose_status');
            });
        }

        // Custom Post Frames — filtered by custom_post_id + status
        if (Schema::hasTable('custom_post_frame')) {
            Schema::table('custom_post_frame', function (Blueprint $table) {
                $table->index(['custom_post_id', 'status'], 'idx_cpf_postid_status');
            });
        }

        // Stories — filtered by status, ordered by id
        if (Schema::hasTable('stories')) {
            Schema::table('stories', function (Blueprint $table) {
                $table->index('status', 'idx_stories_status');
            });
        }

        // Feature Posts — ordered by id
        if (Schema::hasTable('feature_posts')) {
            Schema::table('feature_posts', function (Blueprint $table) {
                $table->index('type', 'idx_featurepost_type');
            });
        }

        // Business Categories — filtered by status
        if (Schema::hasTable('business_categories')) {
            Schema::table('business_categories', function (Blueprint $table) {
                $table->index('status', 'idx_bizcat_status');
            });
        }

        // Earning History — lookup by user_id
        if (Schema::hasTable('earning_histories')) {
            Schema::table('earning_histories', function (Blueprint $table) {
                $table->index('user_id', 'idx_earning_uid');
            });
        }

        // Custom Frames — lookup by user_id
        if (Schema::hasTable('custom_frames')) {
            Schema::table('custom_frames', function (Blueprint $table) {
                $table->index('user_id', 'idx_customframe_uid');
            });
        }

        // Users — lookup by api_token, referral_code, email, mobile_no
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('api_token', 'idx_users_apitoken');
                $table->index('referral_code', 'idx_users_referralcode');
            });
        }
    }

    public function down()
    {
        // Safely drop all indexes — wrapped in try/catch to handle missing tables/indexes
        $drops = [
            'festivals' => ['idx_festivals_status_date', 'idx_festivals_activation_date'],
            'festivals_post' => ['idx_festpost_fid_status', 'idx_festpost_language'],
            'categories' => ['idx_categories_status'],
            'category_post' => ['idx_catpost_cid_status', 'idx_catpost_language'],
            'business' => ['idx_business_uid_status_default'],
            'business_custom_frames' => ['idx_bcf_purpose_status'],
            'user_custom_frame_contents' => ['idx_ucfc_uid_frameid'],
            'videos' => ['idx_videos_type_festival', 'idx_videos_type_category', 'idx_videos_type_business'],
            'stickers' => ['idx_stickers_catid_status'],
            'sticker_categories' => ['idx_stickercat_status'],
            'user_favorite_frames' => ['idx_favframes_uid_fid'],
            'products' => ['idx_products_status', 'idx_products_userid'],
            'product_categories' => ['idx_prodcat_status'],
            'referral_registers' => ['idx_referral_uid_code'],
            'coupon_code_stores' => ['idx_couponstore_code_uid'],
            'user_activities' => ['idx_useract_uid'],
            'user_notifications' => ['idx_usernotif_created'],
            'transactions' => ['idx_transactions_uid'],
            'custom_frame_purposes' => ['idx_cfpurpose_status'],
            'custom_post_frame' => ['idx_cpf_postid_status'],
            'stories' => ['idx_stories_status'],
            'feature_posts' => ['idx_featurepost_type'],
            'business_categories' => ['idx_bizcat_status'],
            'earning_histories' => ['idx_earning_uid'],
            'custom_frames' => ['idx_customframe_uid'],
            'users' => ['idx_users_apitoken', 'idx_users_referralcode'],
        ];

        foreach ($drops as $table => $indexes) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $table) use ($indexes) {
                    foreach ($indexes as $index) {
                        try {
                            $table->dropIndex($index);
                        } catch (\Exception $e) {
                            // Index may not exist, skip
                        }
                    }
                });
            }
        }
    }
};
