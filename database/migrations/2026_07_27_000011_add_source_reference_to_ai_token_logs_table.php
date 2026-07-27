<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ai_token_logs') || Schema::hasColumn('ai_token_logs', 'source_reference')) {
            return;
        }

        Schema::table('ai_token_logs', function (Blueprint $table) {
            $table->string('source_reference', 120)->nullable()->unique()->after('parameters');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ai_token_logs') || !Schema::hasColumn('ai_token_logs', 'source_reference')) {
            return;
        }

        Schema::table('ai_token_logs', function (Blueprint $table) {
            $table->dropUnique(['source_reference']);
            $table->dropColumn('source_reference');
        });
    }
};
