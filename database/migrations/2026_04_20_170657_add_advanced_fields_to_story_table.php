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
        Schema::table('story', function (Blueprint $table) {
            $table->string('expire_type')->default('never')->after('status');
            $table->integer('expire_value')->nullable()->after('expire_type');
            $table->timestamp('expires_at')->nullable()->after('expire_value');
            $table->integer('sort_order')->default(0)->after('expires_at');
            $table->json('story_images')->nullable()->after('image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('story', function (Blueprint $table) {
            $table->dropColumn(['expire_type', 'expire_value', 'expires_at', 'sort_order', 'story_images']);
        });
    }
};
