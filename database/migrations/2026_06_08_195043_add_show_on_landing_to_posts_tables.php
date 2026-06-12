<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShowOnLandingToPostsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('festivals_post', function (Blueprint $table) {
            $table->tinyInteger('show_on_landing')->default(0)->after('status')->comment('0=Hide, 1=Show on Landing');
        });

        Schema::table('category_post', function (Blueprint $table) {
            $table->tinyInteger('show_on_landing')->default(0)->after('status')->comment('0=Hide, 1=Show on Landing');
        });

        Schema::table('custom_post_frame', function (Blueprint $table) {
            $table->tinyInteger('show_on_landing')->default(0)->after('status')->comment('0=Hide, 1=Show on Landing');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('festivals_post', function (Blueprint $table) {
            $table->dropColumn('show_on_landing');
        });

        Schema::table('category_post', function (Blueprint $table) {
            $table->dropColumn('show_on_landing');
        });

        Schema::table('custom_post_frame', function (Blueprint $table) {
            $table->dropColumn('show_on_landing');
        });
    }
}
