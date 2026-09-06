<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A scope can live-link its User Brief Fields to another scope of the
     * same Custom Post Type. General Data, styles, and instructions stay on
     * each target scope and are never copied by this relation.
     */
    public function up(): void
    {
        if (!Schema::hasTable('business_ai_purpose_scopes')
            || Schema::hasColumn('business_ai_purpose_scopes', 'brief_fields_source_scope_id')) {
            return;
        }

        Schema::table('business_ai_purpose_scopes', function (Blueprint $table) {
            $table->unsignedBigInteger('brief_fields_source_scope_id')
                ->nullable()
                ->after('brief_fields');
            $table->index('brief_fields_source_scope_id', 'bai_scope_brief_source_idx');
            $table->foreign('brief_fields_source_scope_id', 'bai_scope_brief_source_fk')
                ->references('id')
                ->on('business_ai_purpose_scopes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('business_ai_purpose_scopes')
            || !Schema::hasColumn('business_ai_purpose_scopes', 'brief_fields_source_scope_id')) {
            return;
        }

        Schema::table('business_ai_purpose_scopes', function (Blueprint $table) {
            $table->dropForeign('bai_scope_brief_source_fk');
            $table->dropIndex('bai_scope_brief_source_idx');
            $table->dropColumn('brief_fields_source_scope_id');
        });
    }
};
