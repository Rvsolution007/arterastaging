<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_combos', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->unsignedBigInteger('column_id');
            $table->json('selected_values');    // ["Black", "Gold", "Silver"]
            $table->string('combo_media_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');
            $table->foreign('column_id')->references('id')->on('catalogue_custom_columns')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_combos');
    }
};
