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
            $table->dropColumn(['general_category_id', 'general_subcategory_id']);
        });

        Schema::dropIfExists('general_subcategories');
        Schema::dropIfExists('general_categories');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('general_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::create('general_subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('general_category_id');
            $table->string('name');
            $table->string('image')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        Schema::table('general_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('general_category_id')->nullable()->after('id');
            $table->unsignedBigInteger('general_subcategory_id')->nullable()->after('general_category_id');
        });
    }
};
