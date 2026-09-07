<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Older Pixel databases can lack this field even though signed reset
        // and identity provisioning queries explicitly select it.
        if (! Schema::hasColumn('users', 'registration_source')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('registration_source')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Preserve customer data and legacy schemas; this is intentionally
        // forward-only rather than dropping a populated compatibility field.
    }
};
