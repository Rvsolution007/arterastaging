<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('challenge_participants', function (Blueprint $table) {
            $table->integer('progress')->default(0)->after('post_id');
            $table->string('status')->default('in_progress')->after('progress'); // in_progress, completed
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('challenge_participants', function (Blueprint $table) {
            $table->dropColumn(['progress', 'status']);
        });
    }
};
