<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCustomFrameContentsTable extends Migration
{
    public function up()
    {
        Schema::create('user_custom_frame_contents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_custom_frame_id');
            $table->integer('product_id');
            $table->longText('generated_content'); // AI-generated JSON with slot values
            $table->timestamps();

            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('business_custom_frame_id')
                  ->references('id')->on('business_custom_frames')
                  ->onDelete('cascade');

            $table->foreign('product_id')
                  ->references('id')->on('product')
                  ->onDelete('cascade');

            // Unique constraint: one generated content per user+frame+product
            $table->unique(['user_id', 'business_custom_frame_id', 'product_id'], 'user_frame_product_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_custom_frame_contents');
    }
}
