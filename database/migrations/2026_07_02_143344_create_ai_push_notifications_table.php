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
        Schema::create('ai_push_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('target_type'); // 'festival', 'retention', 'general'
            $table->unsignedBigInteger('target_id')->nullable(); // e.g. festival_id
            $table->string('title');
            $table->text('body');
            $table->string('status')->default('draft'); // draft, sent
            $table->timestamp('scheduled_for')->nullable();
            $table->integer('predicted_ctr')->default(0); // AI's predicted click-through rate
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
        Schema::dropIfExists('ai_push_notifications');
    }
};
