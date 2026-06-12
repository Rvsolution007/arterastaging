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
        Schema::table('business_custom_frames', function (Blueprint $table) {
            $table->boolean('show_on_landing')->default(0)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_custom_frames', function (Blueprint $table) {
            $table->dropColumn('show_on_landing');
        });
    }
};
