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
        Schema::table('business_products', function (Blueprint $table) {
            if (!Schema::hasColumn('business_products', 'business_type_id')) {
                $table->unsignedBigInteger('business_type_id')->nullable();
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
        Schema::table('business_products', function (Blueprint $table) {
            if (Schema::hasColumn('business_products', 'business_type_id')) {
                $table->dropColumn('business_type_id');
            }
        });
    }
};
