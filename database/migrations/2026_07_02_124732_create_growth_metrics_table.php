<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('growth_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->integer('daily_installs')->default(0);
            $table->integer('daily_active_users')->default(0);
            $table->integer('daily_downloads')->default(0);
            $table->integer('retention_day_1')->default(0);
            $table->integer('retention_day_7')->default(0);
            $table->integer('overall_score')->default(0);
            $table->json('top_opportunities')->nullable();
            $table->json('top_problems')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('growth_metrics');
    }
};
