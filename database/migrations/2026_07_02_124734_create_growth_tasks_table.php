<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('growth_tasks', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('module')->default('daily_analysis');
            $table->string('priority')->default('Medium');
            $table->text('task_description');
            $table->text('recommendation_reason')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('growth_tasks');
    }
};
