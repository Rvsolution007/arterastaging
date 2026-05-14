<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImageTypeSubCategoryTable extends Migration
{
    public function up()
    {
        Schema::create('image_type_sub_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custom_frame_image_type_id');
            $table->integer('business_sub_category_id');
            $table->timestamps();

            $table->foreign('custom_frame_image_type_id')
                  ->references('id')->on('custom_frame_image_types')
                  ->onDelete('cascade');

            $table->foreign('business_sub_category_id')
                  ->references('id')->on('business_sub_category')
                  ->onDelete('cascade');

            $table->unique(['custom_frame_image_type_id', 'business_sub_category_id'], 'img_type_sub_cat_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('image_type_sub_category');
    }
}
