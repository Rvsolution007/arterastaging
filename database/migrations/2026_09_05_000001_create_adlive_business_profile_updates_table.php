<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business', function (Blueprint $table) {
            $table->uuid('profile_version')->nullable()->unique();
        });

        Schema::create('adlive_business_profile_updates', function (Blueprint $table) {
            $table->id();
            $table->uuid('request_id')->unique();
            $table->char('request_fingerprint', 64);
            $table->string('source', 32);
            $table->unsignedBigInteger('artera_user_id')->index();
            $table->unsignedBigInteger('artera_business_id')->index();
            $table->json('changed_fields');
            $table->dateTimeTz('occurred_at');
            $table->string('resulting_profile_version', 128);
            $table->timestamps();

            $table->index(
                ['artera_user_id', 'artera_business_id'],
                'adlive_profile_updates_owner_business_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adlive_business_profile_updates');

        Schema::table('business', function (Blueprint $table) {
            $table->dropUnique(['profile_version']);
            $table->dropColumn('profile_version');
        });
    }
};
