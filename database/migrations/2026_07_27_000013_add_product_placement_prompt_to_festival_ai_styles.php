<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['festival_ai_style_presets', 'festival_ai_styles'] as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'product_placement_prompt')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->longText('product_placement_prompt')->nullable()->after('prompt_text');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['festival_ai_styles', 'festival_ai_style_presets'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'product_placement_prompt')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('product_placement_prompt');
                });
            }
        }
    }
};
