<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('business_ai_purposes') && !Schema::hasColumn('business_ai_purposes', 'product_prompt')) {
            Schema::table('business_ai_purposes', function (Blueprint $table) {
                $table->text('product_prompt')->nullable()->after('base_prompt');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business_ai_purposes') && Schema::hasColumn('business_ai_purposes', 'product_prompt')) {
            Schema::table('business_ai_purposes', function (Blueprint $table) {
                $table->dropColumn('product_prompt');
            });
        }
    }
};
