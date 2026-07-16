<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('poster_maker', function (Blueprint $table) {
            $table->integer('render_version')->default(1)->after('paid');
        });
        Schema::table('editor_templates', function (Blueprint $table) {
            $table->integer('render_version')->default(1)->after('category');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('poster_maker', function (Blueprint $table) {
            $table->dropColumn('render_version');
        });
        Schema::table('editor_templates', function (Blueprint $table) {
            $table->dropColumn('render_version');
        });
    }
};
