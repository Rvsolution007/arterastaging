<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('festival_ai_brand_chrome_presets')) {
            Schema::table('festival_ai_brand_chrome_presets', function (Blueprint $table) {
                if (!Schema::hasColumn('festival_ai_brand_chrome_presets', 'overlay_enabled')) {
                    $table->boolean('overlay_enabled')->default(true)->after('footer_prompt');
                }
                if (!Schema::hasColumn('festival_ai_brand_chrome_presets', 'header_height_percent')) {
                    $table->unsignedTinyInteger('header_height_percent')->default(12)->after('overlay_enabled');
                }
                if (!Schema::hasColumn('festival_ai_brand_chrome_presets', 'footer_height_percent')) {
                    $table->unsignedTinyInteger('footer_height_percent')->default(10)->after('header_height_percent');
                }
                if (!Schema::hasColumn('festival_ai_brand_chrome_presets', 'panel_style')) {
                    $table->string('panel_style', 30)->default('adaptive')->after('footer_height_percent');
                }
                if (!Schema::hasColumn('festival_ai_brand_chrome_presets', 'logo_position')) {
                    $table->string('logo_position', 20)->default('left')->after('panel_style');
                }
                if (!Schema::hasColumn('festival_ai_brand_chrome_presets', 'text_tone')) {
                    $table->string('text_tone', 20)->default('auto')->after('logo_position');
                }
                if (!Schema::hasColumn('festival_ai_brand_chrome_presets', 'max_contact_items')) {
                    $table->unsignedTinyInteger('max_contact_items')->default(4)->after('text_tone');
                }
            });
        }

        if (Schema::hasTable('festival_ai_generations')) {
            Schema::table('festival_ai_generations', function (Blueprint $table) {
                if (!Schema::hasColumn('festival_ai_generations', 'request_diagnostics')) {
                    $table->json('request_diagnostics')->nullable()->after('final_prompt');
                }
                if (!Schema::hasColumn('festival_ai_generations', 'actual_reference_count')) {
                    $table->unsignedTinyInteger('actual_reference_count')->default(0)->after('request_diagnostics');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('festival_ai_generations')) {
            Schema::table('festival_ai_generations', function (Blueprint $table) {
                foreach (['request_diagnostics', 'actual_reference_count'] as $column) {
                    if (Schema::hasColumn('festival_ai_generations', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('festival_ai_brand_chrome_presets')) {
            Schema::table('festival_ai_brand_chrome_presets', function (Blueprint $table) {
                foreach ([
                    'overlay_enabled',
                    'header_height_percent',
                    'footer_height_percent',
                    'panel_style',
                    'logo_position',
                    'text_tone',
                    'max_contact_items',
                ] as $column) {
                    if (Schema::hasColumn('festival_ai_brand_chrome_presets', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
