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
            $table->string('type')->nullable()->after('description'); // festival_post, custom_post, ai_trends_post, any_post
            $table->integer('target_count')->default(1)->after('type');
            $table->integer('target_id')->nullable()->after('target_count'); // specific festival ID
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
            $table->dropColumn(['type', 'target_count', 'target_id']);
        });
    }
};
