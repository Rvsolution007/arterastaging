<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('firebase_uid', 128)->nullable()->after('email')->unique();
            $table->timestamp('google_linked_at')->nullable()->after('firebase_uid');
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_firebase_uid_unique');
            $table->dropColumn(['firebase_uid', 'google_linked_at']);
        });
    }
};
