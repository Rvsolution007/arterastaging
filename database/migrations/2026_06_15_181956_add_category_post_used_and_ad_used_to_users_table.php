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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'category_post_used')) {
                $table->integer('category_post_used')->default(0)->after('festival_post_used');
            }
            if (!Schema::hasColumn('users', 'category_ad_used')) {
                $table->integer('category_ad_used')->default(0);
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['category_post_used', 'category_ad_used']);
        });
    }
};
