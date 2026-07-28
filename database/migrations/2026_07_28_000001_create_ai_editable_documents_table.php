<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_editable_documents')) {
            Schema::create('ai_editable_documents', function (Blueprint $table) {
                $table->id();
                $table->uuid('public_id')->unique();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('festival_ai_generation_id')
                    ->nullable()
                    ->constrained('festival_ai_generations')
                    ->nullOnDelete();
                $table->string('module_version', 32)->default('ai_editable_v1');
                $table->string('document_contract', 80);
                $table->unsignedSmallInteger('schema_version')->default(1);
                $table->string('status', 30)->default('draft')->index();
                $table->json('manifest');
                $table->char('manifest_checksum', 64);
                $table->unsignedInteger('revision')->default(1);
                $table->string('source_image_path')->nullable();
                $table->string('preview_image_path')->nullable();
                $table->string('export_image_path')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'updated_at']);
                $table->index('festival_ai_generation_id');
            });
        }

        if (!Schema::hasTable('ai_editable_document_revisions')) {
            Schema::create('ai_editable_document_revisions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ai_editable_document_id')
                    ->constrained('ai_editable_documents')
                    ->cascadeOnDelete();
                $table->unsignedInteger('revision');
                $table->json('manifest');
                $table->char('manifest_checksum', 64);
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['ai_editable_document_id', 'revision'], 'ai_editable_document_revision_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_editable_document_revisions');
        Schema::dropIfExists('ai_editable_documents');
    }
};
