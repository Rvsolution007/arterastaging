<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SaaS Usage Tracking Migration
     * Tracks how many times a user has consumed each feature this billing cycle.
     * These counters reset monthly via a scheduled job.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('custom_post_used')->default(0)->after('business_limit')
                  ->comment('Custom Posts used this month');
            $table->integer('daily_drip_used')->default(0)->after('custom_post_used')
                  ->comment('Daily Drip posts used this month');
            $table->integer('magic_cloner_used')->default(0)->after('daily_drip_used')
                  ->comment('AI Magic Cloner uses this month');
            $table->timestamp('limits_reset_at')->nullable()->after('magic_cloner_used')
                  ->comment('When the monthly usage counters were last reset');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'custom_post_used',
                'daily_drip_used',
                'magic_cloner_used',
                'limits_reset_at',
            ]);
        });
    }
};
