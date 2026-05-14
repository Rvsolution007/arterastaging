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
        Schema::table('custom_frame_purposes', function (Blueprint $table) {
            $table->string('data_requirement', 30)->default('single_column')->after('ai_prompt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_frame_purposes', function (Blueprint $table) {
            $table->dropColumn('data_requirement');
        });
    }
};
