<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('user_notifications') || Schema::hasColumn('user_notifications', 'user_id')) {
            return;
        }

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_notifications') || !Schema::hasColumn('user_notifications', 'user_id')) {
            return;
        }

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
