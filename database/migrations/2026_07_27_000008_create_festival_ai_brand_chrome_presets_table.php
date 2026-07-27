<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('festival_ai_brand_chrome_presets', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->longText('header_prompt')->nullable();
            $table->longText('footer_prompt')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::table('festival_ai_configs', function (Blueprint $table) {
            $table->foreignId('festival_ai_brand_chrome_preset_id')
                ->nullable()
                ->after('festival_id')
                ->constrained('festival_ai_brand_chrome_presets')
                ->nullOnDelete();
        });

        Schema::table('festival_ai_generations', function (Blueprint $table) {
            $table->json('business_snapshot')->nullable()->after('product_snapshot');
            $table->json('brand_chrome_snapshot')->nullable()->after('business_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('festival_ai_generations', function (Blueprint $table) {
            $table->dropColumn(['business_snapshot', 'brand_chrome_snapshot']);
        });

        Schema::table('festival_ai_configs', function (Blueprint $table) {
            $table->dropForeign(['festival_ai_brand_chrome_preset_id']);
            $table->dropColumn('festival_ai_brand_chrome_preset_id');
        });

        Schema::dropIfExists('festival_ai_brand_chrome_presets');
    }
};
