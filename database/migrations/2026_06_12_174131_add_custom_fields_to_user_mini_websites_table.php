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
        Schema::table('user_mini_websites', function (Blueprint $table) {
            $table->string('business_name')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->text('about_us')->nullable();
            $table->text('products_services')->nullable();
            $table->string('facebook')->nullable();
            $table->string('twitter')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('logo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_mini_websites', function (Blueprint $table) {
            $table->dropColumn([
                'business_name', 'email', 'mobile_no', 'website', 'address',
                'about_us', 'products_services', 'facebook', 'twitter',
                'instagram', 'youtube', 'linkedin', 'logo'
            ]);
        });
    }
};
