<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reward_credit_unlocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('feature_key', 50)->index();
            $table->timestamp('consumed_at')->nullable()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
            $table->index(['user_id', 'feature_key', 'consumed_at'], 'reward_unlock_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_credit_unlocks');
    }
};
