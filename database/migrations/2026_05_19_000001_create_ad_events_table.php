<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ad_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->enum('ad_type', ['banner', 'interstitial', 'rewarded'])->index();
            $table->enum('event', ['impression', 'click', 'completed'])->default('impression');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['ad_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_events');
    }
};
