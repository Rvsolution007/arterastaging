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
        Schema::create('ai_review_replies', function (Blueprint $table) {
            $table->id();
            $table->string('reviewer_name');
            $table->integer('rating'); // 1 to 5
            $table->text('review_text');
            $table->text('ai_reply_draft');
            $table->string('status')->default('pending'); // pending, posted
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
        Schema::dropIfExists('ai_review_replies');
    }
};
