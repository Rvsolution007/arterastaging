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
        Schema::create('template_revisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('frame_id')->index();
            $table->integer('revision_number')->default(1);
            $table->string('file_path');
            $table->longText('schema_json')->nullable();
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
        Schema::dropIfExists('template_revisions');
    }
};
