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
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->integer('monthly_price')->default(0)->after('plan_detail');
            $table->integer('monthly_discount_price')->default(0)->after('monthly_price');
            $table->integer('yearly_price')->default(0)->after('monthly_discount_price');
            $table->integer('yearly_discount_price')->default(0)->after('yearly_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscription_plan', function (Blueprint $table) {
            $table->dropColumn(['monthly_price', 'monthly_discount_price', 'yearly_price', 'yearly_discount_price']);
        });
    }
};
