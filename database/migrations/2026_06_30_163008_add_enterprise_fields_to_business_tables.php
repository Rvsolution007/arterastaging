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
        Schema::table('business_category', function (Blueprint $table) {
            if (!Schema::hasColumn('business_category', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
        });

        Schema::table('business_sub_category', function (Blueprint $table) {
            if (!Schema::hasColumn('business_sub_category', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (!Schema::hasColumn('business_sub_category', 'has_business_type')) {
                $table->boolean('has_business_type')->default(0)->after('slug');
            }
        });

        Schema::table('business_types', function (Blueprint $table) {
            if (!Schema::hasColumn('business_types', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
        });

        Schema::table('business_products', function (Blueprint $table) {
            if (!Schema::hasColumn('business_products', 'slug')) {
                $table->string('slug')->nullable();
            }
            if (!Schema::hasColumn('business_products', 'product_type_id')) {
                $table->unsignedBigInteger('product_type_id')->nullable();
            }
            if (!Schema::hasColumn('business_products', 'brand_id')) {
                $table->unsignedBigInteger('brand_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('business_category', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('business_sub_category', function (Blueprint $table) {
            $table->dropColumn(['slug', 'has_business_type']);
        });
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
        Schema::table('business_products', function (Blueprint $table) {
            $table->dropColumn(['slug', 'product_type_id', 'brand_id']);
        });
    }
};
