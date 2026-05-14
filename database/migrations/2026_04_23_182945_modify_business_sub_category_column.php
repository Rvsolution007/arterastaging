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
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('business_sub_category_id');
            $table->json('business_sub_category_ids')->nullable()->after('business_category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('business_sub_category_ids');
            $table->unsignedBigInteger('business_sub_category_id')->nullable()->after('business_category_id');
        });
    }
};
