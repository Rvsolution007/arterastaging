<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->integer('ai_image_limit')->default(0)->after('photoroom_bg_limit');
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->integer('ai_image_used')->default(0)->after('photoroom_bg_used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->dropColumn('ai_image_limit');
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('ai_image_used');
        });
    }
};
