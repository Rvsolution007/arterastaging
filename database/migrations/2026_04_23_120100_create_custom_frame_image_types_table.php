<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFrameImageTypesTable extends Migration
{
    public function up()
    {
        Schema::create('custom_frame_image_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // transparent, full, etc.
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_frame_image_types');
    }
}
