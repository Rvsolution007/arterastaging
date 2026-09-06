<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('subscription_plan') && !Schema::hasColumn('subscription_plan', 'business_ai_generation_credit_cost')) {
            Schema::table('subscription_plan', function (Blueprint $table) {
                $table->unsignedInteger('business_ai_generation_credit_cost')->default(1)->after('ai_image_limit');
            });
        }

        if (Schema::hasTable('business_ai_generations')) {
            Schema::table('business_ai_generations', function (Blueprint $table) {
                if (!Schema::hasColumn('business_ai_generations', 'credit_cost')) {
                    $table->unsignedInteger('credit_cost')->default(1)->after('generation_kind');
                }
                if (!Schema::hasColumn('business_ai_generations', 'is_saved_draft')) {
                    $table->boolean('is_saved_draft')->default(false)->after('status');
                    $table->index('is_saved_draft', 'bai_generation_draft_idx');
                }
                if (!Schema::hasColumn('business_ai_generations', 'saved_as_draft_at')) {
                    $table->timestamp('saved_as_draft_at')->nullable()->after('is_saved_draft');
                }
            });
        }

        if (!Schema::hasTable('business_ai_reference_uploads')) {
            Schema::create('business_ai_reference_uploads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->uuid('public_id')->unique();
                $table->string('original_name', 255);
                $table->string('mime_type', 100);
                $table->unsignedInteger('size');
                $table->string('path', 500);
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at'], 'bai_reference_upload_user_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('business_ai_reference_uploads')) {
            Schema::drop('business_ai_reference_uploads');
        }

        if (Schema::hasTable('business_ai_generations')) {
            $columns = [];
            if (Schema::hasColumn('business_ai_generations', 'saved_as_draft_at')) {
                $columns[] = 'saved_as_draft_at';
            }
            if (Schema::hasColumn('business_ai_generations', 'is_saved_draft')) {
                Schema::table('business_ai_generations', function (Blueprint $table) {
                    $table->dropIndex('bai_generation_draft_idx');
                });
                $columns[] = 'is_saved_draft';
            }
            if (Schema::hasColumn('business_ai_generations', 'credit_cost')) {
                $columns[] = 'credit_cost';
            }
            if ($columns !== []) {
                Schema::table('business_ai_generations', function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('subscription_plan') && Schema::hasColumn('subscription_plan', 'business_ai_generation_credit_cost')) {
            Schema::table('subscription_plan', function (Blueprint $table) {
                $table->dropColumn('business_ai_generation_credit_cost');
            });
        }
    }
};
