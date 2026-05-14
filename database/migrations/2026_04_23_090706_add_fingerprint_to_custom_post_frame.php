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
        Schema::table('custom_post_frame', function (Blueprint $table) {
            $table->json('fingerprint')->nullable()->after('aspect_ratio');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_post_frame', function (Blueprint $table) {
            $table->dropColumn('fingerprint');
        });
    }
};
