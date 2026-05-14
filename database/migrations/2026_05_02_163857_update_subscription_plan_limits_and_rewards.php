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
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->integer('custom_post_ad_reward_limit')->default(5)->after('custom_post_ad_reward');
            $table->integer('daily_drip_ad_reward_limit')->default(5)->after('daily_drip_ad_reward');
            $table->integer('magic_cloner_ad_reward_limit')->default(5)->after('magic_cloner_ad_reward');
            $table->integer('festival_post_ad_reward_limit')->default(5)->after('festival_post_ad_reward');
            $table->integer('business_category_ad_reward_limit')->default(5)->after('business_category_ad_reward');

            $table->dropColumn(['daily_drip_can_edit', 'daily_drip_can_choose']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('custom_post_ad_used')->default(0);
            $table->integer('daily_drip_ad_used')->default(0);
            $table->integer('magic_cloner_ad_used')->default(0);
            $table->integer('festival_post_ad_used')->default(0);
            $table->integer('business_category_ad_used')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->dropColumn([
                'custom_post_ad_reward_limit',
                'daily_drip_ad_reward_limit',
                'magic_cloner_ad_reward_limit',
                'festival_post_ad_reward_limit',
                'business_category_ad_reward_limit'
            ]);

            $table->boolean('daily_drip_can_edit')->default(false);
            $table->boolean('daily_drip_can_choose')->default(false);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'custom_post_ad_used',
                'daily_drip_ad_used',
                'magic_cloner_ad_used',
                'festival_post_ad_used',
                'business_category_ad_used'
            ]);
        });
    }
};
