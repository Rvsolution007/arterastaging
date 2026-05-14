<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('id');
            $table->string('category_name')->nullable()->after('product_category_id');
            $table->string('sku')->nullable()->after('title');
            $table->string('unit')->default('pcs')->after('description');
            $table->integer('mrp')->default(0)->after('unit');           // In paise
            $table->integer('sale_price')->default(0)->after('mrp');     // In paise
            $table->decimal('gst_percent', 5, 2)->default(0)->after('sale_price');
            $table->string('hsn_code')->nullable()->after('gst_percent');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropColumn(['user_id', 'category_name', 'sku', 'unit', 'mrp', 'sale_price', 'gst_percent', 'hsn_code']);
        });
    }
};
