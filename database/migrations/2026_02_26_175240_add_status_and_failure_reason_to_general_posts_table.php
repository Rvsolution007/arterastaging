<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('general_posts', function (Blueprint $table) {
            $table->string('zip_name')->nullable()->after('zip_file_id');
            $table->string('process_status')->default('success')->after('task_name'); // success, failed, pending
            $table->text('failure_reason')->nullable()->after('process_status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('general_posts', function (Blueprint $table) {
            $table->dropColumn(['zip_name', 'process_status', 'failure_reason']);
        });
    }
};
