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
            if (!Schema::hasColumn('business_ai_generations', 'root_generation_id')) {
                $table->unsignedBigInteger('root_generation_id')->nullable()->after('subscription_id');
                $table->index('root_generation_id', 'bai_generation_root_idx');
            }
            if (!Schema::hasColumn('business_ai_generations', 'parent_generation_id')) {
                $table->unsignedBigInteger('parent_generation_id')->nullable()->after('root_generation_id');
                $table->index('parent_generation_id', 'bai_generation_parent_idx');
            }
            if (!Schema::hasColumn('business_ai_generations', 'generation_kind')) {
                $table->string('generation_kind', 30)->default('initial')->after('parent_generation_id');
                $table->index('generation_kind', 'bai_generation_kind_idx');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('business_ai_generations')) {
            return;
        }

        $columns = ['root_generation_id', 'parent_generation_id', 'generation_kind'];
        $existing = array_values(array_filter($columns, fn ($column) => Schema::hasColumn('business_ai_generations', $column)));
        if ($existing !== []) {
            Schema::table('business_ai_generations', function (Blueprint $table) use ($existing) {
                if (in_array('root_generation_id', $existing, true)) {
                    $table->dropIndex('bai_generation_root_idx');
                }
                if (in_array('parent_generation_id', $existing, true)) {
                    $table->dropIndex('bai_generation_parent_idx');
                }
                if (in_array('generation_kind', $existing, true)) {
                    $table->dropIndex('bai_generation_kind_idx');
                }
                $table->dropColumn($existing);
            });
        }
    }
};
