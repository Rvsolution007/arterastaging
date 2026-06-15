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
        Schema::table('user_mini_websites', function (Blueprint $table) {
            $table->text('map_url')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('clients_count')->nullable();
            $table->string('years_experience')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_mini_websites', function (Blueprint $table) {
            $table->dropColumn(['map_url', 'whatsapp_number', 'clients_count', 'years_experience']);
        });
    }
};
