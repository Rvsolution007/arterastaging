<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveMagicClonerFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('magic_cloner_settings');

        if (Schema::hasColumn('users', 'magic_cloner_used')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn(['magic_cloner_used', 'magic_cloner_ad_used']);
            });
        }

        if (Schema::hasColumn('subscription_plan', 'magic_cloner_limit')) {
            Schema::table('subscription_plan', function (Blueprint $table) {
                $table->dropColumn(['magic_cloner_limit', 'magic_cloner_ad_reward', 'magic_cloner_ad_reward_limit']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No down migration
    }
}
