<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('adlive_access_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('ticket_hash', 64)->unique();
            $table->unsignedBigInteger('artera_user_id')->index();
            $table->unsignedBigInteger('artera_business_id')->index();
            $table->json('payload');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();

            $table->index(['artera_user_id', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('adlive_access_tickets');
    }
};
