<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessCustomFramesTable extends Migration
{
    public function up()
    {
        Schema::create('business_custom_frames', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('custom_frame_purpose_id');
            $table->unsignedBigInteger('custom_frame_image_type_id');
            $table->string('zip_file_path');
            $table->longText('json_rules')->nullable(); // Extracted JSON from zip
            $table->boolean('status')->default(1);
            $table->timestamps();

            $table->foreign('custom_frame_purpose_id')
                  ->references('id')->on('custom_frame_purposes')
                  ->onDelete('cascade');

            $table->foreign('custom_frame_image_type_id')
                  ->references('id')->on('custom_frame_image_types')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('business_custom_frames');
    }
}
