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
    public function up()
    {
        if (Schema::hasTable('business_custom_frames')) {
            Schema::table('business_custom_frames', function (Blueprint $table) {
                if (!Schema::hasColumn('business_custom_frames', 'render_version')) {
                    $table->integer('render_version')->default(1)->after('zip_file_path');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('business_custom_frames')) {
            Schema::table('business_custom_frames', function (Blueprint $table) {
                if (Schema::hasColumn('business_custom_frames', 'render_version')) {
                    $table->dropColumn('render_version');
                }
            });
        }
    }
};
