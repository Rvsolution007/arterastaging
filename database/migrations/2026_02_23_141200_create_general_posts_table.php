<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('general_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('general_category_id')->nullable();
            $table->unsignedBigInteger('general_subcategory_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('frame_image')->nullable();
            $table->integer('status')->default(1);
            $table->integer('paid')->default(1);
            $table->string('height')->nullable();
            $table->string('width')->nullable();
            $table->string('image_type')->nullable();
            $table->string('aspect_ratio')->nullable();
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
        Schema::dropIfExists('general_posts');
    }
};
