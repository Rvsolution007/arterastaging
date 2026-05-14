<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('catalogue_custom_columns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('name');                    // "Material", "Color", "Finish"
            $table->string('slug');                    // "material", "color", "finish"
            $table->enum('type', ['text', 'textarea', 'number', 'select', 'multiselect', 'boolean'])->default('text');
            $table->json('options')->nullable();       // For select/multiselect: ["Oak","Teak","Pine"]
            $table->boolean('is_required')->default(false);
            $table->boolean('is_unique')->default(false);   // ONE column as unique identifier (SKU/Code)
            $table->boolean('is_combo')->default(false);     // Variation matrix (Size, Color, Finish)
            $table->boolean('is_variation_field')->default(false); // Per-variation value (Price per combo)
            $table->boolean('is_system')->default(false);    // System fields can't be deleted
            $table->boolean('is_active')->default(true);
            $table->boolean('is_category')->default(false);  // Exactly ONE - links to categories
            $table->boolean('is_title')->default(false);     // Exactly ONE - display name
            $table->boolean('show_on_list')->default(false);
            $table->boolean('show_in_ai')->default(true);    // Visible to AI for post generation
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'is_combo']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('catalogue_custom_columns');
    }
};
