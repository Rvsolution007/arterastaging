<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_install_events', function (Blueprint $table) {
            $table->id();
            $table->string('device_hash', 64)->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('event_type', 20)->index();
            $table->string('event_key', 64)->unique();
            $table->string('platform', 20)->default('android');
            $table->string('app_version', 50)->nullable();
            $table->string('device_model', 120)->nullable();
            $table->string('os_version', 120)->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['event_type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_install_events');
    }
};
