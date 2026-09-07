<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adlive_identity_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_id')->unique();
            $table->string('event_type', 32);
            // Delivery reconstructs canonical data by ID. No identity or
            // credential payload is ever persisted in this outbox.
            $table->unsignedBigInteger('artera_user_id')->index();
            $table->unsignedBigInteger('artera_business_id')->nullable()->index();
            $table->timestampTz('occurred_at');
            $table->unsignedInteger('delivery_attempts')->default(0);
            $table->timestampTz('processing_at')->nullable()->index();
            $table->timestampTz('sent_at')->nullable()->index();
            $table->string('last_failure', 64)->nullable();
            $table->timestamps();

            $table->index(['sent_at', 'created_at'], 'adlive_identity_event_pending_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adlive_identity_events');
    }
};
