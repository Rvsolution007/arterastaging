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
            $table->boolean('custom_post_ad_reward')->default(true);
            $table->boolean('daily_drip_ad_reward')->default(true);
            $table->boolean('magic_cloner_ad_reward')->default(true);
            $table->boolean('festival_post_ad_reward')->default(true);
            $table->boolean('category_ad_reward')->default(true);
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
                'custom_post_ad_reward',
                'daily_drip_ad_reward',
                'magic_cloner_ad_reward',
                'festival_post_ad_reward',
                'category_ad_reward'
            ]);
        });
    }
};
