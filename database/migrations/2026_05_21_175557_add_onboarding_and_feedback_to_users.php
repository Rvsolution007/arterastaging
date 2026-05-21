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
        Schema::table('users', function (Blueprint $table) {
            $table->json('completed_onboarding_steps')->nullable()->after('last_payment_failure_reason');
            $table->timestamp('last_feedback_asked_at')->nullable()->after('completed_onboarding_steps');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['completed_onboarding_steps', 'last_feedback_asked_at']);
        });
    }
};
