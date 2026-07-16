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
        Schema::create('regression_test_logs', function (Blueprint $table) {
            $table->id();
            $table->string('trigger')->default('manual'); // 'deploy', 'manual', 'cron'
            $table->integer('total_frames_tested')->default(0);
            $table->integer('passed')->default(0);
            $table->integer('failed')->default(0);
            $table->longText('results')->nullable(); // JSON array of per-frame results
            $table->string('status')->default('running'); // 'running', 'completed', 'failed'
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
        Schema::dropIfExists('regression_test_logs');
    }
};
