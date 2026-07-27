<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_token_logs')) {
            return;
        }

        Schema::table('ai_token_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('ai_token_logs', 'request_type')) {
                $table->string('request_type', 30)->default('text')->after('model');
            }
            if (!Schema::hasColumn('ai_token_logs', 'image_count')) {
                $table->unsignedInteger('image_count')->default(0)->after('total_tokens');
            }
            if (!Schema::hasColumn('ai_token_logs', 'usage_source')) {
                $table->string('usage_source', 30)->default('provider')->after('image_count');
            }
            if (!Schema::hasColumn('ai_token_logs', 'parameters')) {
                $table->json('parameters')->nullable()->after('usage_source');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_token_logs')) {
            return;
        }

        Schema::table('ai_token_logs', function (Blueprint $table) {
            foreach (['request_type', 'image_count', 'usage_source', 'parameters'] as $column) {
                if (Schema::hasColumn('ai_token_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
