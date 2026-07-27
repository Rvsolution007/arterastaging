<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_ai_image_accesses')) {
            Schema::create('subscription_ai_image_accesses', function (Blueprint $table) {
                $table->id();
                // subscription_plan uses a signed INT primary key in this app.
                $table->integer('subscription_id');
                $table->foreignId('ai_image_model_id')->constrained('ai_image_models')->cascadeOnDelete();
                $table->json('allowed_qualities');
                $table->json('allowed_size_keys')->nullable();
                $table->unsignedTinyInteger('max_reference_images')->default(0);
                $table->boolean('allow_refinement')->default(true);
                $table->boolean('status')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                // MySQL permits index identifiers of at most 64 characters.
                $table->unique(['subscription_id', 'ai_image_model_id'], 'sub_ai_image_access_unique');
                $table->foreign('subscription_id')->references('id')->on('subscription_plan')->cascadeOnDelete();
            });
        } else {
            // Recover safely if MySQL created the table before a later index
            // statement in this migration failed.
            Schema::table('subscription_ai_image_accesses', function (Blueprint $table) {
                $table->unique(['subscription_id', 'ai_image_model_id'], 'sub_ai_image_access_unique');
                $table->foreign('subscription_id')->references('id')->on('subscription_plan')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_ai_image_accesses');
    }
};
