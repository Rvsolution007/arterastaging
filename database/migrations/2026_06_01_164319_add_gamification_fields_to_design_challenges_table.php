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
        Schema::table('design_challenges', function (Blueprint $table) {
            $table->integer('streak_goal_days')->nullable()->after('target_id');
            $table->boolean('push_notification_enabled')->default(0)->after('streak_goal_days');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('design_challenges', function (Blueprint $table) {
            $table->dropColumn(['streak_goal_days', 'push_notification_enabled']);
        });
    }
};
