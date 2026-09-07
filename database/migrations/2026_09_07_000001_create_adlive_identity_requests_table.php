<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adlive_identity_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->char('request_fingerprint', 64);
            $table->string('operation', 32);
            $table->string('source', 32);
            $table->unsignedBigInteger('artera_user_id')->nullable()->index();
            // Field names only, never values.
            $table->json('changed_fields')->nullable();
            $table->timestampTz('occurred_at');
            $table->timestamps();

            $table->index(['operation', 'created_at'], 'adlive_identity_request_operation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adlive_identity_requests');
    }
};
