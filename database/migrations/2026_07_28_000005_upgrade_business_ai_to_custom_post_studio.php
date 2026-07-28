<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_ai_header_footer_styles')) {
            Schema::create('business_ai_header_footer_styles', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 150);
                $table->longText('header_prompt')->nullable();
                $table->longText('footer_prompt')->nullable();
                $table->boolean('overlay_enabled')->default(true);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('business_ai_purpose_styles')) {
            Schema::create('business_ai_purpose_styles', function (Blueprint $table) {
                $table->unsignedBigInteger('business_ai_purpose_id');
                $table->unsignedBigInteger('business_ai_style_id');
                $table->timestamps();
                $table->unique(['business_ai_purpose_id', 'business_ai_style_id'], 'business_ai_type_style_uq');
                $table->foreign('business_ai_purpose_id', 'business_ai_type_style_type_fk')
                    ->references('id')->on('business_ai_purposes')->onDelete('cascade');
                $table->foreign('business_ai_style_id', 'business_ai_type_style_style_fk')
                    ->references('id')->on('business_ai_styles')->onDelete('cascade');
            });
        }

        Schema::table('business_ai_purposes', function (Blueprint $table) {
            if (!Schema::hasColumn('business_ai_purposes', 'business_ai_header_footer_style_id')) {
                $table->unsignedBigInteger('business_ai_header_footer_style_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('business_ai_purposes', 'allowed_size_keys')) {
                $table->json('allowed_size_keys')->nullable()->after('brief_fields');
            }
            if (!Schema::hasColumn('business_ai_purposes', 'product_upload_enabled')) {
                $table->boolean('product_upload_enabled')->default(true)->after('allowed_size_keys');
            }
            if (!Schema::hasColumn('business_ai_purposes', 'product_required')) {
                $table->boolean('product_required')->default(false)->after('product_upload_enabled');
            }
            if (!Schema::hasColumn('business_ai_purposes', 'max_product_references')) {
                $table->unsignedTinyInteger('max_product_references')->default(4)->after('product_required');
            }
            if (!Schema::hasColumn('business_ai_purposes', 'change_instruction_limit')) {
                $table->unsignedSmallInteger('change_instruction_limit')->default(300)->after('max_product_references');
            }
        });

        $this->addHeaderFooterForeignKey();
        $defaultHeaderFooterId = $this->seedHeaderFooterStyle();
        $this->moveExistingTypeStylesToLibrary();

        DB::table('business_ai_purposes')->whereNull('business_ai_header_footer_style_id')->update([
            'business_ai_header_footer_style_id' => $defaultHeaderFooterId,
            'allowed_size_keys' => json_encode(['square', 'landscape', 'portrait']),
            'product_upload_enabled' => true,
            'product_required' => false,
            'max_product_references' => 4,
            'change_instruction_limit' => 300,
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (Schema::hasTable('business_ai_purposes')) {
            Schema::table('business_ai_purposes', function (Blueprint $table) {
                if (Schema::hasColumn('business_ai_purposes', 'business_ai_header_footer_style_id')) {
                    $table->dropForeign('business_ai_purpose_header_footer_fk');
                    $table->dropColumn([
                        'business_ai_header_footer_style_id', 'allowed_size_keys', 'product_upload_enabled',
                        'product_required', 'max_product_references', 'change_instruction_limit',
                    ]);
                }
            });
        }
        Schema::dropIfExists('business_ai_purpose_styles');
        Schema::dropIfExists('business_ai_header_footer_styles');
    }

    private function addHeaderFooterForeignKey(): void
    {
        if ($this->foreignKeyExists('business_ai_purpose_header_footer_fk')) {
            return;
        }
        Schema::table('business_ai_purposes', function (Blueprint $table) {
            $table->foreign('business_ai_header_footer_style_id', 'business_ai_purpose_header_footer_fk')
                ->references('id')->on('business_ai_header_footer_styles')->nullOnDelete();
        });
    }

    private function seedHeaderFooterStyle(): int
    {
        $existing = DB::table('business_ai_header_footer_styles')->orderBy('sort_order')->value('id');
        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('business_ai_header_footer_styles')->insertGetId([
            'name' => 'Clean Business Header & Footer',
            'header_prompt' => 'Reserve a clean upper safe zone for the business logo and name. Keep it subtle, premium and separate from the main product artwork.',
            'footer_prompt' => 'Reserve a clear lower safe zone for concise contact details and a small call-to-action. Keep the area readable and uncluttered.',
            'overlay_enabled' => true,
            'sort_order' => 1,
            'status' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** Convert the initial type-owned defaults into one reusable style library. */
    private function moveExistingTypeStylesToLibrary(): void
    {
        $styles = DB::table('business_ai_styles')->orderBy('id')->get();
        $canonicalByKey = [];
        foreach ($styles as $style) {
            $key = (string) $style->key;
            $canonicalId = $canonicalByKey[$key] ?? null;
            if (!$canonicalId) {
                $canonicalByKey[$key] = (int) $style->id;
                $canonicalId = (int) $style->id;
            }

            if ($style->business_ai_purpose_id) {
                DB::table('business_ai_purpose_styles')->insertOrIgnore([
                    'business_ai_purpose_id' => $style->business_ai_purpose_id,
                    'business_ai_style_id' => $canonicalId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            if ((int) $style->id !== $canonicalId) {
                DB::table('business_ai_styles')->where('id', $style->id)->delete();
            }
        }

        // This Laravel version needs Doctrine DBAL for ->change(). Use one
        // MariaDB ALTER instead, so deployment does not require a package.
        if (!$this->indexExists('business_ai_styles', 'business_ai_style_key_uq')) {
            // The old composite unique index also supported the legacy
            // purpose foreign key. Add a dedicated index before removing it.
            if (!$this->indexExists('business_ai_styles', 'business_ai_style_purpose_idx')) {
                DB::statement('ALTER TABLE `business_ai_styles` ADD INDEX `business_ai_style_purpose_idx` (`business_ai_purpose_id`)');
            }
            DB::statement('ALTER TABLE `business_ai_styles` DROP INDEX `business_ai_style_purpose_key_uq`, MODIFY `business_ai_purpose_id` BIGINT UNSIGNED NULL, ADD UNIQUE INDEX `business_ai_style_key_uq` (`key`)');
        }
        DB::table('business_ai_styles')->update(['business_ai_purpose_id' => null]);
    }

    private function foreignKeyExists(string $name): bool
    {
        return (int) DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', $name)
            ->count() > 0;
    }

    private function indexExists(string $table, string $name): bool
    {
        return (int) DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $name)
            ->count() > 0;
    }
};
