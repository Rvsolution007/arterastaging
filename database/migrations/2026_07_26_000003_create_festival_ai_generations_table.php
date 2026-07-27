<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festival_ai_generations', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // subscription_plan and festivals use signed INT primary keys.
            $table->integer('subscription_id')->nullable();
            $table->integer('festival_id');
            $table->foreignId('festival_ai_style_id')->nullable()->constrained('festival_ai_styles')->nullOnDelete();
            $table->foreignId('ai_image_model_id')->constrained('ai_image_models')->restrictOnDelete();
            $table->string('provider', 50);
            $table->string('provider_model_id', 100);
            $table->string('quality', 30);
            $table->string('size_key', 50);
            $table->string('size_value', 50);
            $table->longText('user_instruction')->nullable();
            $table->longText('final_prompt');
            $table->json('product_snapshot')->nullable();
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('quota_reserved_at')->nullable();
            $table->timestamp('quota_refunded_at')->nullable();
            $table->string('generated_image_path')->nullable();
            $table->string('error_code', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->foreign('subscription_id')->references('id')->on('subscription_plan')->nullOnDelete();
            $table->foreign('festival_id')->references('id')->on('festivals')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('festival_ai_generations');
    }
};
