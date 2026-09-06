<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adlive_identity_provision_outbox', function (Blueprint $table) {
            $table->id();
            // IDs are sufficient to reconstruct the current canonical profile.
            // Deliberately do not persist email, phone, password, hash, token,
            // signed payload, response body, or shared secret.
            $table->unsignedBigInteger('artera_user_id')->index();
            $table->unsignedBigInteger('artera_business_id')->index();
            $table->uuid('sync_batch_id');
            $table->unsignedSmallInteger('delivery_order');
            $table->string('signup_source', 32);
            $table->unsignedInteger('delivery_attempts')->default(0);
            $table->string('last_failure', 64)->nullable();
            $table->timestampTz('processing_at')->nullable()->index();
            $table->timestampTz('sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['sent_at', 'created_at'], 'adlive_identity_outbox_pending_index');
            $table->index(['sync_batch_id', 'delivery_order'], 'adlive_identity_outbox_batch_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adlive_identity_provision_outbox');
    }
};
