<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golden_renders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frame_id')->index();
            $table->string('zip_name')->index();
            $table->integer('render_version')->default(1);
            $table->longText('web_computed')->nullable();    // JSON: per-layer web computed values
            $table->longText('native_computed')->nullable();  // JSON: per-layer native computed values
            $table->string('web_thumbnail_path')->nullable(); // Path to web preview screenshot
            $table->string('native_snapshot_path')->nullable(); // Path to native preview screenshot
            $table->string('source')->default('publish');     // 'publish', 'migration', 'manual'
            $table->timestamps();

            // Unique constraint: one golden per frame per version
            $table->unique(['frame_id', 'render_version'], 'golden_frame_version_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('golden_renders');
    }
};
