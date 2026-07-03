<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. App Installs Table (For precise UTM and device tracking)
        Schema::create('app_installs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('utm_source')->nullable(); // e.g., organic, referral, paid, playstore
            $table->string('utm_medium')->nullable(); 
            $table->string('utm_campaign')->nullable();
            $table->string('device_model')->nullable();
            $table->string('os_version')->nullable();
            $table->string('country')->nullable();
            $table->string('state')->nullable();
            $table->string('city')->nullable();
            $table->timestamps();
        });

        // 2. User Sessions Table (For precise session duration tracking)
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('start_time')->useCurrent();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->string('platform')->default('android');
            $table->timestamps();
        });

        // 3. Play Store Reviews Cache Table (For AI classification)
        Schema::create('play_store_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('review_id')->unique();
            $table->string('author_name')->nullable();
            $table->integer('star_rating');
            $table->text('review_text')->nullable();
            $table->timestamp('review_date')->nullable();
            // AI Classification Fields
            $table->string('classification')->nullable(); // Positive, Negative, Bug, Feature Request, Design
            $table->string('sentiment')->nullable(); // Positive, Neutral, Negative
            $table->boolean('ai_processed')->default(false);
            $table->timestamps();
        });

        // 4. AI Growth Reports Table (Midnight Cron outputs)
        Schema::create('ai_growth_reports', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->json('top_opportunities')->nullable();
            $table->json('top_problems')->nullable();
            $table->json('execution_plan')->nullable(); // Actionable Tasks
            $table->integer('overall_growth_score')->default(0);
            $table->integer('content_score')->default(0);
            $table->integer('retention_score')->default(0);
            $table->integer('engagement_score')->default(0);
            $table->integer('revenue_score')->default(0);
            $table->text('raw_ai_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ai_growth_reports');
        Schema::dropIfExists('play_store_reviews');
        Schema::dropIfExists('user_sessions');
        Schema::dropIfExists('app_installs');
        Schema::dropIfExists('growth_os_tables');
    }
};
