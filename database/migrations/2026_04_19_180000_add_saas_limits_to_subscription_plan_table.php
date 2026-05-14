<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SaaS Package Limits Migration
     * Adds dynamic per-package feature limits to the subscription_plan table.
     * These limits are fully adjustable by the admin without code changes.
     */
    public function up()
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->integer('custom_post_edit_limit')->default(5)->after('business_limit')
                  ->comment('Max editable Custom Posts (with choose) per month');
            $table->integer('daily_drip_limit')->default(15)->after('custom_post_edit_limit')
                  ->comment('Max automated Daily Drip posts per month');
            $table->integer('magic_cloner_limit')->default(1)->after('daily_drip_limit')
                  ->comment('Max AI Magic Cloner uses per month');
            $table->boolean('daily_drip_can_edit')->default(false)->after('magic_cloner_limit')
                  ->comment('Whether daily drip posts allow editing in this package');
            $table->boolean('daily_drip_can_choose')->default(false)->after('daily_drip_can_edit')
                  ->comment('Whether user can choose post category in daily drip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->dropColumn([
                'custom_post_edit_limit',
                'daily_drip_limit',
                'magic_cloner_limit',
                'daily_drip_can_edit',
                'daily_drip_can_choose',
            ]);
        });
    }
};
