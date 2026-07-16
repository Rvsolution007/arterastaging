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
        Schema::table('coupon_code', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_id')->nullable()->after('limit');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('coupon_code', function (Blueprint $table) {
            $table->dropColumn('subscription_id');
        });
    }
};
