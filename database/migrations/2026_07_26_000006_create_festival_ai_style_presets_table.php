<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festival_ai_style_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->longText('prompt_text');
            $table->json('preview_images')->nullable();
            $table->json('allowed_size_keys')->nullable();
            $table->boolean('product_required')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('festival_ai_styles', function (Blueprint $table) {
            $table->foreignId('festival_ai_style_preset_id')
                ->nullable()
                ->after('festival_ai_config_id')
                ->constrained('festival_ai_style_presets')
                ->nullOnDelete();
            $table->unique(
                ['festival_ai_config_id', 'festival_ai_style_preset_id'],
                'festival_ai_style_preset_unique'
            );
        });

        // Preserve existing per-festival styles: each becomes a reusable
        // library item and remains selected for its original festival.
        $legacyStyles = DB::table('festival_ai_styles')->get();
        foreach ($legacyStyles as $legacyStyle) {
            $presetId = DB::table('festival_ai_style_presets')->insertGetId([
                'name' => $legacyStyle->name,
                'prompt_text' => $legacyStyle->prompt_text,
                'preview_images' => $legacyStyle->preview_images,
                'allowed_size_keys' => $legacyStyle->allowed_size_keys,
                'product_required' => $legacyStyle->product_required,
                'sort_order' => $legacyStyle->sort_order,
                'status' => $legacyStyle->status,
                'created_at' => $legacyStyle->created_at,
                'updated_at' => $legacyStyle->updated_at,
            ]);

            DB::table('festival_ai_styles')
                ->where('id', $legacyStyle->id)
                ->update(['festival_ai_style_preset_id' => $presetId]);
        }
    }

    public function down(): void
    {
        Schema::table('festival_ai_styles', function (Blueprint $table) {
            $table->dropUnique('festival_ai_style_preset_unique');
            $table->dropForeign(['festival_ai_style_preset_id']);
            $table->dropColumn('festival_ai_style_preset_id');
        });

        Schema::dropIfExists('festival_ai_style_presets');
    }
};
