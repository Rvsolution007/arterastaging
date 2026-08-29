<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Some local Artera databases contain the original migration record but
     * not its table. Create only the missing SSO ticket store; never alter an
     * already populated table or recreate ticket data.
     */
    public function up(): void
    {
        if (Schema::hasTable('adlive_access_tickets')) {
            return;
        }

        Schema::create('adlive_access_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ticket_hash', 64)->unique();
            $table->unsignedBigInteger('artera_user_id')->index();
            $table->unsignedBigInteger('artera_business_id')->index();
            $table->json('payload');
            $table->timestamp('expires_at')->index();
            $table->timestamp('used_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /** The repair migration intentionally never deletes issued tickets. */
    public function down(): void
    {
        // no-op
    }
};
