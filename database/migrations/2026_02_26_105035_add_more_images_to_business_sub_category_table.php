<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('business_sub_category', function (Blueprint $table) {
            $table->json('more_images')->nullable()->after('image_2');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('business_sub_category', function (Blueprint $table) {
            $table->dropColumn('more_images');
        });
    }
};
