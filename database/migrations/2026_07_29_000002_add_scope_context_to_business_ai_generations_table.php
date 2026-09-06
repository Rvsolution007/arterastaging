<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_ai_generations')) {
            return;
        }

        Schema::table('business_ai_generations', function (Blueprint $table) {
            if (!Schema::hasColumn('business_ai_generations', 'business_ai_purpose_scope_id')) {
                $table->unsignedBigInteger('business_ai_purpose_scope_id')->nullable()->after('purpose_title');
                $table->index('business_ai_purpose_scope_id', 'bai_generation_scope_idx');
                $table->foreign('business_ai_purpose_scope_id', 'bai_generation_scope_fk')
                    ->references('id')->on('business_ai_purpose_scopes')->nullOnDelete();
            }
            if (!Schema::hasColumn('business_ai_generations', 'scope_snapshot')) {
                $table->json('scope_snapshot')->nullable()->after('business_snapshot');
            }
            if (!Schema::hasColumn('business_ai_generations', 'content_preview')) {
                $table->json('content_preview')->nullable()->after('scope_snapshot');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('business_ai_generations')) {
            return;
        }

        Schema::table('business_ai_generations', function (Blueprint $table) {
            if (Schema::hasColumn('business_ai_generations', 'business_ai_purpose_scope_id')) {
                $table->dropForeign('bai_generation_scope_fk');
                $table->dropIndex('bai_generation_scope_idx');
                $table->dropColumn('business_ai_purpose_scope_id');
            }
            $columns = [];
            if (Schema::hasColumn('business_ai_generations', 'scope_snapshot')) {
                $columns[] = 'scope_snapshot';
            }
            if (Schema::hasColumn('business_ai_generations', 'content_preview')) {
                $columns[] = 'content_preview';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
