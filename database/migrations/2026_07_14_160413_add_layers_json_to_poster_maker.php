<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Render Version 4: Add layers_json column to poster_maker table.
     * 
     * This stores the complete Legacy JSON (same format the app already reads)
     * directly in the database row. When populated, the API serves this instead
     * of reading from disk files — eliminating file I/O at API time.
     * 
     * LONGTEXT because template JSON can be 5-50KB per template.
     * Using LONGTEXT instead of JSON type for MariaDB compatibility.
     */
    public function up(): void
    {
        Schema::table('poster_maker', function (Blueprint $table) {
            $table->longText('layers_json')->nullable()->after('render_version');
        });
    }

    public function down(): void
    {
        Schema::table('poster_maker', function (Blueprint $table) {
            $table->dropColumn('layers_json');
        });
    }
};
