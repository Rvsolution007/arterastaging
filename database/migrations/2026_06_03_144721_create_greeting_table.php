<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGreetingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('greeting', function (Blueprint $table) {
            $table->id();
            $table->string('greeting_type')->nullable(); // "simple" or "editable"
            $table->integer('greeting_category_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('language_id')->nullable();
            $table->string('zip_name')->nullable();
            $table->text('frame_image')->nullable();
            $table->integer('status')->default(1);
            $table->integer('paid')->default(0);
            $table->integer('height')->default(1024);
            $table->integer('width')->default(1024);
            $table->string('image_type')->default('square');
            $table->string('aspect_ratio')->default('1:1');
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
        Schema::dropIfExists('greeting');
    }
}
