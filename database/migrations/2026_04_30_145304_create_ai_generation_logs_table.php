<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_generation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_generation_batch_id')->nullable()->constrained('ai_generation_batches')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('product')->onDelete('set null');
            $table->longText('raw_prompt')->nullable();
            $table->longText('raw_response')->nullable();
            $table->integer('tokens_used')->default(0);
            $table->string('status')->default('success'); // success, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_generation_logs');
    }
};
