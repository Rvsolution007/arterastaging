<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('catalogue_custom_values', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->unsignedBigInteger('column_id');
            $table->text('value')->nullable();  // JSON-encoded for multiselect, plain for text/number
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');
            $table->foreign('column_id')->references('id')->on('catalogue_custom_columns')->onDelete('cascade');
            $table->unique(['product_id', 'column_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('catalogue_custom_values');
    }
};
