<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A deleted subcategory must delete its scoped General Data. The original
     * null-on-delete relation could silently turn Eye Clinic data into a
     * category-wide fallback, which is unsafe for the AI content flow.
     */
    public function up(): void
    {
        if (!Schema::hasTable('business_ai_purpose_scopes')) {
            return;
        }

        // Repair any records created before the nested subcategory UI: the
        // subcategory remains authoritative for a scope's parent category.
        $this->synchroniseScopeCategories();

        Schema::table('business_ai_purpose_scopes', function (Blueprint $table) {
            $table->dropForeign('bai_scope_subcategory_fk');
            $table->foreign('business_sub_category_id', 'bai_scope_subcategory_fk')
                ->references('id')
                ->on('business_sub_category')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('business_ai_purpose_scopes')) {
            return;
        }

        Schema::table('business_ai_purpose_scopes', function (Blueprint $table) {
            $table->dropForeign('bai_scope_subcategory_fk');
            $table->foreign('business_sub_category_id', 'bai_scope_subcategory_fk')
                ->references('id')
                ->on('business_sub_category')
                ->nullOnDelete();
        });
    }

    private function synchroniseScopeCategories(): void
    {
        if (!Schema::hasTable('business_sub_category')) {
            return;
        }

        DB::table('business_ai_purpose_scopes')
            ->whereNotNull('business_sub_category_id')
            ->orderBy('id')
            ->chunkById(100, function ($scopes) {
                $subCategoryIds = collect($scopes)
                    ->pluck('business_sub_category_id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                if ($subCategoryIds->isEmpty()) {
                    return;
                }

                $categoryIds = DB::table('business_sub_category')
                    ->whereIn('id', $subCategoryIds)
                    ->pluck('business_category_id', 'id');

                foreach ($scopes as $scope) {
                    $categoryId = $categoryIds->get($scope->business_sub_category_id);
                    if ($categoryId && (int) $scope->business_category_id !== (int) $categoryId) {
                        DB::table('business_ai_purpose_scopes')
                            ->where('id', $scope->id)
                            ->update([
                                'business_category_id' => $categoryId,
                                'updated_at' => now(),
                            ]);
                    }
                }
            });
    }
};
