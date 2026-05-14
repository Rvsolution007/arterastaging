<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks which product images a user has selected/used in which editor template.
     * Used by the "My Products" panel to show ✅ already-used indicator.
     */
    public function up()
    {
        Schema::create('editor_product_selections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->string('template_id')->nullable(); // frame identifier (e.g., "bcf_12", "poster_5")
            $table->string('image_url');
            $table->enum('image_mode', ['full', 'transparent'])->default('full');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('product_id');
            $table->index(['user_id', 'template_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('editor_product_selections');
    }
};
