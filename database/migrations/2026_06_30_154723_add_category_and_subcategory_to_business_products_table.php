<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('business_products', 'business_category_id')) {
            Schema::table('business_products', function (Blueprint $table) {
                $table->unsignedBigInteger('business_category_id')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('business_products', 'business_sub_category_id')) {
            Schema::table('business_products', function (Blueprint $table) {
                $table->unsignedBigInteger('business_sub_category_id')->nullable()->after('business_category_id');
            });
        }
        
        DB::statement('ALTER TABLE business_products MODIFY business_type_id BIGINT UNSIGNED NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE business_products MODIFY business_type_id BIGINT UNSIGNED NOT NULL;');
        Schema::table('business_products', function (Blueprint $table) {
            $table->dropColumn('business_category_id');
            $table->dropColumn('business_sub_category_id');
        });
    }
};
