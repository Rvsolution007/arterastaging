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
        Schema::table('general_posts', function (Blueprint $table) {
            $table->unsignedInteger('views_count')->default(0)->after('status');
            $table->unsignedInteger('downloads_count')->default(0)->after('views_count');
            $table->unsignedInteger('shares_count')->default(0)->after('downloads_count');
            $table->unsignedInteger('favorites_count')->default(0)->after('shares_count');
            $table->decimal('popularity_score', 8, 2)->default(0.00)->after('favorites_count');
            $table->decimal('growth_score', 8, 2)->default(0.00)->after('popularity_score');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_posts', function (Blueprint $table) {
            $table->dropColumn([
                'views_count',
                'downloads_count',
                'shares_count',
                'favorites_count',
                'popularity_score',
                'growth_score'
            ]);
        });
    }
};
