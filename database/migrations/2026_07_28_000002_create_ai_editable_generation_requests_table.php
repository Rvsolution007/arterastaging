<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_editable_generation_requests')) {
            Schema::create('ai_editable_generation_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            // Explicit short FK names are required on MariaDB (64 character
            // constraint-name limit). Keeping columns separate also lets a
            // previously interrupted deployment resume safely below.
            $table->unsignedBigInteger('festival_ai_generation_id')->unique();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('ai_editable_document_id')->nullable();
            $table->string('status', 30)->default('queued')->index();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            });
        }

        Schema::table('ai_editable_generation_requests', function (Blueprint $table) {
            $table->foreign('festival_ai_generation_id', 'ai_edit_req_festival_fk')
                ->references('id')->on('festival_ai_generations')->cascadeOnDelete();
            $table->foreign('user_id', 'ai_edit_req_user_fk')
                ->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('ai_editable_document_id', 'ai_edit_req_document_fk')
                ->references('id')->on('ai_editable_documents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_editable_generation_requests');
    }
};
