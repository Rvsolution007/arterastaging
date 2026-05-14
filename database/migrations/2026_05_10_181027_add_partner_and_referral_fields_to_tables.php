<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPartnerAndReferralFieldsToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_partner')->default(0)->after('referral_code');
            $table->decimal('partner_commission_percent', 5, 2)->nullable()->after('is_partner');
            $table->decimal('total_partner_earnings', 10, 2)->default(0)->after('partner_commission_percent');
        });

        Schema::table('coupon_code', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_id')->nullable()->after('limit');
            $table->boolean('is_first_time_only')->default(1)->after('partner_id');
            // Adding a foreign key might fail if the current table is MyISAM, we will keep it simple for now without hard foreign key constraints, or add it later if needed.
        });

        Schema::table('transaction', function (Blueprint $table) {
            $table->unsignedBigInteger('coupon_code_id')->nullable()->after('subscription_id');
            $table->unsignedBigInteger('partner_id')->nullable()->after('coupon_code_id');
            $table->decimal('partner_commission_amount', 10, 2)->default(0)->after('partner_id');
            $table->string('partner_commission_status')->default('pending')->after('partner_commission_amount'); // pending, approved, paid, cancelled
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_partner', 'partner_commission_percent', 'total_partner_earnings']);
        });

        Schema::table('coupon_code', function (Blueprint $table) {
            $table->dropColumn(['partner_id', 'is_first_time_only']);
        });

        Schema::table('transaction', function (Blueprint $table) {
            $table->dropColumn(['coupon_code_id', 'partner_id', 'partner_commission_amount', 'partner_commission_status']);
        });
    }
}
