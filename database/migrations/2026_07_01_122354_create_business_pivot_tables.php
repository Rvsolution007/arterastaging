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
        Schema::create('business_sub_category_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_sub_category_id');
            $table->timestamps();

            $table->index('business_id');
            $table->index('business_sub_category_id');
        });

        Schema::create('business_type_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('business_type_id');
            $table->timestamps();

            $table->index('business_id');
            $table->index('business_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_type_mappings');
        Schema::dropIfExists('business_sub_category_mappings');
    }
};
