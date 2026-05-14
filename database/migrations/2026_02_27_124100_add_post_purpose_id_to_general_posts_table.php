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
            if (!Schema::hasColumn('general_posts', 'post_purpose_id')) {
                $table->unsignedBigInteger('post_purpose_id')->nullable()->after('business_sub_category_id');
                $table->foreign('post_purpose_id')->references('id')->on('post_purposes')->onDelete('set null');
            }
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
            $table->dropForeign(['post_purpose_id']);
            $table->dropColumn('post_purpose_id');
        });
    }
};
