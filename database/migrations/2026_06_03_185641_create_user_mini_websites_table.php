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
        Schema::create('user_mini_websites', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('business_id')->nullable();
            $table->unsignedBigInteger('mini_website_template_id')->nullable();
            $table->string('slug')->unique();
            $table->integer('views_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_mini_websites');
    }
};
