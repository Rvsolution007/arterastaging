<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('category_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('category_id');
            $table->string('category_type')->comment('festival, business, custom');
            $table->integer('total_views')->default(0);
            $table->integer('total_downloads')->default(0);
            $table->integer('template_count')->default(0);
            $table->integer('demand_score')->default(0);
            $table->timestamps();
            $table->unique(['date', 'category_id', 'category_type']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('category_metrics');
    }
};
