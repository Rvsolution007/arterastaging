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
    public function up(): void
    {
        Schema::create('aso_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword');
            $table->integer('current_rank');
            $table->integer('previous_rank')->nullable();
            $table->integer('search_volume')->default(0);
            $table->string('difficulty')->default('medium'); // low, medium, high
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
        Schema::dropIfExists('aso_keywords');
    }
};
