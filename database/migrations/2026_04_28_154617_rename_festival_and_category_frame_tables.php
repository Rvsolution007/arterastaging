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
        Schema::rename('festivals_frame', 'festivals_post');
        Schema::rename('category_frame', 'category_post');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('festivals_post', 'festivals_frame');
        Schema::rename('category_post', 'category_frame');
    }
};
