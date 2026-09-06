<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business', function (Blueprint $table) {
            // This is intentionally distinct from business_type_id, which
            // points at Pixel's separate, optional global taxonomy table.
            $table->string('adlive_business_type', 32)->nullable()->after('business_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('business', function (Blueprint $table) {
            $table->dropColumn('adlive_business_type');
        });
    }
};
