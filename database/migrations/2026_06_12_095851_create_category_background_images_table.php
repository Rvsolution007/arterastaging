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
        Schema::create('category_background_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_category_id');
            $table->string('image');
            $table->string('aspect_ratio', 10); // '1:1', '16:9', '9:16'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('category_background_images');
    }
};
