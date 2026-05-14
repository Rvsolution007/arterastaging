<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id();
            $table->integer('product_id');
            $table->json('combination');        // {"finish":"Black","size":"Large"}
            $table->string('combination_key');  // "black|large" (sorted, lowercase)
            $table->integer('price')->default(0);       // In paise (cents)
            $table->decimal('discount', 5, 2)->default(0);
            $table->json('custom_fields')->nullable();   // Per-variation custom values
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('product')->onDelete('cascade');
            $table->index(['product_id', 'combination_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variations');
    }
};
