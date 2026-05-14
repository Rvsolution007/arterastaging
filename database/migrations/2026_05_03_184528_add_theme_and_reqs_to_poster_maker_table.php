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
        Schema::table('poster_maker', function (Blueprint $table) {
            $table->string('theme')->nullable()->default('all');
            $table->integer('req_address')->default(0);
            $table->integer('req_email')->default(0);
            $table->integer('req_phone')->default(0);
            $table->integer('req_website')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('poster_maker', function (Blueprint $table) {
            $table->dropColumn(['theme', 'req_address', 'req_email', 'req_phone', 'req_website']);
        });
    }
};
