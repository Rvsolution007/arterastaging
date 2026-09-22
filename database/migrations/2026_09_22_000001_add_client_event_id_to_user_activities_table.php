<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            // Nullable preserves all historical activity rows. New mobile
            // session events receive a UUID and are deduplicated per user.
            $table->uuid('client_event_id')->nullable()->after('user_id');
            $table->unique(['user_id', 'client_event_id'], 'user_activity_client_event_unique');
        });
    }

    public function down(): void
    {
        Schema::table('user_activities', function (Blueprint $table) {
            $table->dropUnique('user_activity_client_event_unique');
            $table->dropColumn('client_event_id');
        });
    }
};
