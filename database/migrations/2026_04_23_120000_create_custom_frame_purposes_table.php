<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomFramePurposesTable extends Migration
{
    public function up()
    {
        Schema::create('custom_frame_purposes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('ai_prompt');
            $table->boolean('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('custom_frame_purposes');
    }
}
