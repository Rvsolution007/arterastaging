<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Custom Post Type can have a different approved content setup for each
     * business category/subcategory.  The parent type remains the source of
     * reusable defaults; this table stores only the scoped differences.
     */
    public function up(): void
    {
        if (!Schema::hasTable('business_ai_purpose_scopes')) {
            Schema::create('business_ai_purpose_scopes', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_ai_purpose_id');
                // These legacy category tables use signed INT primary keys
                // (not Laravel's unsigned BIGINT default), so the foreign
                // keys must use the exact same type.
                $table->integer('business_category_id');
                // A null subcategory is an intentional category-level fallback.
                $table->integer('business_sub_category_id')->nullable();
                // Null means "inherit the parent Custom Post Type fields".
                $table->json('brief_fields')->nullable();
                // Admin-approved, manually entered reusable points only.
                $table->json('general_data')->nullable();
                $table->longText('content_instruction')->nullable();
                $table->boolean('status')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();

                $table->index(['business_ai_purpose_id', 'business_category_id'], 'bai_scope_purpose_category_idx');
                $table->index(['business_category_id', 'business_sub_category_id', 'status'], 'bai_scope_category_sub_status_idx');
                $table->foreign('business_ai_purpose_id', 'bai_scope_purpose_fk')
                    ->references('id')->on('business_ai_purposes')->cascadeOnDelete();
                $table->foreign('business_category_id', 'bai_scope_category_fk')
                    ->references('id')->on('business_category')->cascadeOnDelete();
                $table->foreign('business_sub_category_id', 'bai_scope_subcategory_fk')
                    ->references('id')->on('business_sub_category')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('business_ai_purpose_scope_styles')) {
            Schema::create('business_ai_purpose_scope_styles', function (Blueprint $table) {
                $table->unsignedBigInteger('business_ai_purpose_scope_id');
                $table->unsignedBigInteger('business_ai_style_id');
                $table->timestamps();

                $table->unique(['business_ai_purpose_scope_id', 'business_ai_style_id'], 'bai_scope_style_unique');
                $table->foreign('business_ai_purpose_scope_id', 'bai_scope_style_scope_fk')
                    ->references('id')->on('business_ai_purpose_scopes')->cascadeOnDelete();
                $table->foreign('business_ai_style_id', 'bai_scope_style_style_fk')
                    ->references('id')->on('business_ai_styles')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_ai_purpose_scope_styles');
        Schema::dropIfExists('business_ai_purpose_scopes');
    }
};
