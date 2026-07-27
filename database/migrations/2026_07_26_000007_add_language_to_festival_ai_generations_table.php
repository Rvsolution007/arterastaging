<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('festival_ai_generations', function (Blueprint $table) {
            // Keep this independent from the language table's legacy key type.
            // The final prompt remains a permanent snapshot if a language is
            // later removed by an administrator.
            $table->integer('language_id')->nullable()->after('festival_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('festival_ai_generations', function (Blueprint $table) {
            $table->dropIndex(['language_id']);
            $table->dropColumn('language_id');
        });
    }
};
