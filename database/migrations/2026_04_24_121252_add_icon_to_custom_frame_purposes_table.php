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
            $table->string('icon')->nullable();
        });
        \DB::statement('ALTER TABLE custom_frame_purposes MODIFY ai_prompt TEXT NULL;');
        \DB::statement('ALTER TABLE custom_frame_purposes MODIFY data_requirement VARCHAR(255) NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_frame_purposes', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
        \DB::statement('ALTER TABLE custom_frame_purposes MODIFY ai_prompt TEXT NOT NULL;');
        \DB::statement('ALTER TABLE custom_frame_purposes MODIFY data_requirement VARCHAR(255) NOT NULL;');
    }
};
