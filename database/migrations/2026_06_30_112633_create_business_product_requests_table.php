<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessProductRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('business_product_requests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id');
            $table->integer('business_sub_category_id');
            $table->string('requested_name');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->integer('resolved_product_id')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('business_sub_category_id')->references('id')->on('business_sub_category')->onDelete('cascade');
            $table->foreign('resolved_product_id')->references('id')->on('business_products')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_product_requests');
    }
}
