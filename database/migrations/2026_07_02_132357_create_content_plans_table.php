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
        Schema::create('content_plans', function (Blueprint $table) {
            $table->id();
            $table->date('plan_date');
            $table->string('content_type'); // 'festival', 'business_category', 'general'
            $table->unsignedBigInteger('target_id')->nullable(); // festival_id or category_id
            $table->string('target_name'); // e.g. "Diwali" or "Jewellery"
            $table->integer('suggested_templates')->default(10);
            $table->string('status')->default('pending'); // pending, completed
            $table->integer('opportunity_score')->default(0); // High score = must do
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
        Schema::dropIfExists('content_plans');
    }
};
