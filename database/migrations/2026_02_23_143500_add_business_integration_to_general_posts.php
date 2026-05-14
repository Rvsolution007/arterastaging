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
        Schema::table('general_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('business_category_id')->nullable()->after('id');
            $table->unsignedBigInteger('business_sub_category_id')->nullable()->after('business_category_id');
            $table->unsignedBigInteger('product_id')->nullable()->after('business_sub_category_id');
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
            $table->dropColumn(['business_category_id', 'business_sub_category_id', 'product_id']);
        });
    }
};
