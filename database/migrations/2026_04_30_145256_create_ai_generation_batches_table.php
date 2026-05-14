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
        Schema::create('ai_generation_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_custom_frame_id')->constrained('business_custom_frames')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, processing, completed, failed
            $table->integer('total_users')->default(0);
            $table->integer('processed_users')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->decimal('total_cost', 10, 4)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generation_batches');
    }
};
