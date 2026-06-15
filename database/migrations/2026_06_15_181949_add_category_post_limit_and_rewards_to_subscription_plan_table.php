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
            if (!Schema::hasColumn('subscription_plan', 'category_post_limit')) {
                $table->integer('category_post_limit')->default(0)->after('festival_post_limit');
            }
            if (!Schema::hasColumn('subscription_plan', 'category_ad_reward')) {
                $table->integer('category_ad_reward')->default(0);
            }
            if (!Schema::hasColumn('subscription_plan', 'category_ad_reward_limit')) {
                $table->integer('category_ad_reward_limit')->default(0);
            }
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
            $table->dropColumn(['category_post_limit', 'category_ad_reward', 'category_ad_reward_limit']);
        });
    }
};
