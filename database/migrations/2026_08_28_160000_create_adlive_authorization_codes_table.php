<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adlive_authorization_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('code_hash', 64)->unique();
            $table->string('client_id', 64);
            $table->string('redirect_uri', 2048);
            $table->string('code_challenge', 128);
            $table->unsignedBigInteger('artera_user_id')->index();
            $table->unsignedBigInteger('artera_business_id')->index();
            $table->json('payload');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adlive_authorization_codes');
    }
};
