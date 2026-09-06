<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business')) {
            Schema::table('business', function (Blueprint $table) {
                if (!Schema::hasColumn('business', 'brand_primary_color')) {
                    $table->string('brand_primary_color', 7)->nullable()->after('hidden_frame_fields');
                }
                if (!Schema::hasColumn('business', 'brand_secondary_color')) {
                    $table->string('brand_secondary_color', 7)->nullable()->after('brand_primary_color');
                }
            });
        }

        if (Schema::hasTable('business_ai_generations') && !Schema::hasColumn('business_ai_generations', 'palette_snapshot')) {
            Schema::table('business_ai_generations', function (Blueprint $table) {
                $table->json('palette_snapshot')->nullable()->after('scope_snapshot');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business_ai_generations') && Schema::hasColumn('business_ai_generations', 'palette_snapshot')) {
            Schema::table('business_ai_generations', function (Blueprint $table) {
                $table->dropColumn('palette_snapshot');
            });
        }

        if (Schema::hasTable('business')) {
            $columns = [];
            if (Schema::hasColumn('business', 'brand_primary_color')) {
                $columns[] = 'brand_primary_color';
            }
            if (Schema::hasColumn('business', 'brand_secondary_color')) {
                $columns[] = 'brand_secondary_color';
            }
            if ($columns !== []) {
                Schema::table('business', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
