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
            $table->integer('festival_post_limit')->default(0)->after('custom_post_edit_limit');
            $table->integer('business_category_post_limit')->default(0)->after('festival_post_limit');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('festival_post_used')->default(0)->after('custom_post_used');
            $table->integer('business_category_post_used')->default(0)->after('festival_post_used');
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
            $table->dropColumn(['festival_post_limit', 'business_category_post_limit']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['festival_post_used', 'business_category_post_used']);
        });
    }
};
