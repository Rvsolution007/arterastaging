<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAiAnalysisToClientErrorsTable extends Migration
{
    public function up()
    {
        Schema::table('client_errors', function (Blueprint $table) {
            $table->enum('ai_severity', ['critical', 'high', 'medium', 'low', 'info'])->nullable()->after('status');
            $table->string('ai_category', 100)->nullable()->after('ai_severity');
            $table->text('ai_root_cause')->nullable()->after('ai_category');
            $table->text('ai_suggested_fix')->nullable()->after('ai_root_cause');
            $table->unsignedTinyInteger('ai_confidence')->nullable()->after('ai_suggested_fix');
            $table->boolean('ai_is_ux_bug')->default(false)->after('ai_confidence');
            $table->string('ai_pattern_group', 100)->nullable()->after('ai_is_ux_bug');
            $table->timestamp('ai_analyzed_at')->nullable()->after('ai_pattern_group');

            $table->index('ai_severity');
            $table->index('ai_category');
            $table->index('ai_is_ux_bug');
            $table->index('ai_pattern_group');
        });
    }

    public function down()
    {
        Schema::table('client_errors', function (Blueprint $table) {
            $table->dropIndex(['ai_severity']);
            $table->dropIndex(['ai_category']);
            $table->dropIndex(['ai_is_ux_bug']);
            $table->dropIndex(['ai_pattern_group']);
            $table->dropColumn([
                'ai_severity', 'ai_category', 'ai_root_cause', 'ai_suggested_fix',
                'ai_confidence', 'ai_is_ux_bug', 'ai_pattern_group', 'ai_analyzed_at'
            ]);
        });
    }
}
