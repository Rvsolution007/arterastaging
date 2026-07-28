<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_ai_generations')) {
            Schema::create('business_ai_generations', function (Blueprint $table) {
                $table->id();
                $table->uuid('request_id')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->integer('subscription_id')->nullable();
                $table->string('purpose_key', 80);
                $table->string('purpose_title', 160);
                $table->string('style_key', 80);
                $table->string('style_name', 160);
                $table->integer('language_id')->nullable();
                $table->foreignId('ai_image_model_id')->constrained('ai_image_models')->restrictOnDelete();
                $table->string('provider', 50);
                $table->string('provider_model_id', 100);
                $table->string('quality', 30);
                $table->string('size_key', 50);
                $table->string('size_value', 50);
                $table->json('brief');
                $table->longText('user_instruction')->nullable();
                $table->longText('final_prompt');
                $table->json('request_diagnostics')->nullable();
                $table->json('product_snapshot')->nullable();
                $table->json('business_snapshot')->nullable();
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
            });
        }

        if (!Schema::hasTable('business_ai_editable_requests')) {
            Schema::create('business_ai_editable_requests', function (Blueprint $table) {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('business_ai_generation_id')->unique()->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                // The standalone document migration may be deployed separately
                // on older installations. Create the compatible key column
                // first, then add its FK below only when that table exists.
                $table->unsignedBigInteger('ai_editable_document_id')->nullable();
                $table->string('status', 30)->default('queued')->index();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
                $table->index(['user_id', 'created_at']);
            });
        }

        if (Schema::hasTable('ai_editable_documents') && !Schema::hasColumn('ai_editable_documents', 'business_ai_generation_id')) {
            Schema::table('ai_editable_documents', function (Blueprint $table) {
                $table->foreignId('business_ai_generation_id')->nullable()->after('festival_ai_generation_id')
                    ->constrained('business_ai_generations')->nullOnDelete();
                $table->index('business_ai_generation_id');
            });
        }

        if (Schema::hasTable('ai_editable_documents')) {
            Schema::table('business_ai_editable_requests', function (Blueprint $table) {
                $table->foreign('ai_editable_document_id', 'business_ai_editable_document_fk')
                    ->references('id')
                    ->on('ai_editable_documents')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ai_editable_documents') && Schema::hasColumn('ai_editable_documents', 'business_ai_generation_id')) {
            Schema::table('ai_editable_documents', function (Blueprint $table) {
                $table->dropForeign(['business_ai_generation_id']);
                $table->dropIndex(['business_ai_generation_id']);
                $table->dropColumn('business_ai_generation_id');
            });
        }
        if (Schema::hasTable('business_ai_editable_requests')) {
            Schema::table('business_ai_editable_requests', function (Blueprint $table) {
                $table->dropForeign('business_ai_editable_document_fk');
            });
        }
        Schema::dropIfExists('business_ai_editable_requests');
        Schema::dropIfExists('business_ai_generations');
    }
};
