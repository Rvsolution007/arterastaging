<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_image_models')) {
            Schema::create('ai_image_models', function (Blueprint $table) {
                $table->id();
                $table->string('provider', 50)->default('openai');
                $table->string('model_id', 100);
                $table->string('display_name', 150);
                $table->text('description')->nullable();
                $table->json('quality_options')->nullable();
                $table->json('size_options')->nullable();
                $table->string('default_quality', 30)->nullable();
                $table->string('default_size_key', 50)->nullable();
                $table->boolean('supports_reference_images')->default(false);
                $table->boolean('supports_edits')->default(false);
                $table->boolean('supports_transparent_background')->default(false);
                $table->unsignedTinyInteger('max_reference_images')->default(0);
                $table->unsignedSmallInteger('estimated_seconds')->nullable();
                $table->json('pricing_config')->nullable();
                $table->boolean('is_active')->default(false);
                $table->boolean('is_recommended')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->unique(['provider', 'model_id']);
            });
        }

        if (!Schema::hasTable('festival_ai_configs')) {
            Schema::create('festival_ai_configs', function (Blueprint $table) {
                $table->id();
                // The established festivals table uses a signed INT primary key.
                $table->integer('festival_id')->unique();
                $table->boolean('is_enabled')->default(false);
                $table->longText('base_prompt')->nullable();
                $table->text('product_prompt')->nullable();
                $table->json('allowed_size_keys')->nullable();
                $table->unsignedTinyInteger('max_products')->default(3);
                $table->boolean('allow_product_upload')->default(true);
                $table->boolean('require_product_name_for_upload')->default(true);
                $table->unsignedSmallInteger('max_user_instruction_characters')->default(250);
                $table->timestamps();

                $table->foreign('festival_id')->references('id')->on('festivals')->cascadeOnDelete();
            });
        } else {
            // MySQL leaves earlier CREATE TABLE statements in place when a
            // later foreign-key statement fails. Repair that partial local
            // migration safely before adding the compatible constraint.
            DB::statement('ALTER TABLE `festival_ai_configs` MODIFY `festival_id` INT NOT NULL');
            Schema::table('festival_ai_configs', function (Blueprint $table) {
                $table->unique('festival_id');
                $table->foreign('festival_id')->references('id')->on('festivals')->cascadeOnDelete();
            });
        }

        if (!Schema::hasTable('festival_ai_styles')) {
            Schema::create('festival_ai_styles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('festival_ai_config_id')->constrained('festival_ai_configs')->cascadeOnDelete();
                $table->string('name', 150);
                $table->longText('prompt_text');
                $table->json('preview_images')->nullable();
                $table->json('allowed_size_keys')->nullable();
                $table->boolean('product_required')->default(false);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('festival_ai_styles');
        Schema::dropIfExists('festival_ai_configs');
        Schema::dropIfExists('ai_image_models');
    }
};
