<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add SEO fields to business_category, business_sub_category,
     * business_types, festivals, and blogs tables for programmatic SEO.
     */
    public function up()
    {
        // Business Category SEO fields
        if (Schema::hasTable('business_category')) {
            Schema::table('business_category', function (Blueprint $table) {
                if (!Schema::hasColumn('business_category', 'seo_title')) {
                    $table->string('seo_title', 255)->nullable()->after('status');
                }
                if (!Schema::hasColumn('business_category', 'seo_description')) {
                    $table->text('seo_description')->nullable()->after('seo_title');
                }
                if (!Schema::hasColumn('business_category', 'seo_content')) {
                    $table->longText('seo_content')->nullable()->after('seo_description');
                }
                if (!Schema::hasColumn('business_category', 'seo_faqs')) {
                    $table->json('seo_faqs')->nullable()->after('seo_content');
                }
                if (!Schema::hasColumn('business_category', 'seo_image')) {
                    $table->string('seo_image', 500)->nullable()->after('seo_faqs');
                }
            });
        }

        // Business Sub-Category SEO fields
        if (Schema::hasTable('business_sub_category')) {
            Schema::table('business_sub_category', function (Blueprint $table) {
                if (!Schema::hasColumn('business_sub_category', 'seo_title')) {
                    $table->string('seo_title', 255)->nullable()->after('status');
                }
                if (!Schema::hasColumn('business_sub_category', 'seo_description')) {
                    $table->text('seo_description')->nullable()->after('seo_title');
                }
                if (!Schema::hasColumn('business_sub_category', 'seo_content')) {
                    $table->longText('seo_content')->nullable()->after('seo_description');
                }
                if (!Schema::hasColumn('business_sub_category', 'seo_faqs')) {
                    $table->json('seo_faqs')->nullable()->after('seo_content');
                }
            });
        }

        // Business Types SEO fields
        if (Schema::hasTable('business_types')) {
            Schema::table('business_types', function (Blueprint $table) {
                if (!Schema::hasColumn('business_types', 'seo_title')) {
                    $table->string('seo_title', 255)->nullable()->after('status');
                }
                if (!Schema::hasColumn('business_types', 'seo_description')) {
                    $table->text('seo_description')->nullable()->after('seo_title');
                }
                if (!Schema::hasColumn('business_types', 'seo_content')) {
                    $table->longText('seo_content')->nullable()->after('seo_description');
                }
                if (!Schema::hasColumn('business_types', 'seo_faqs')) {
                    $table->json('seo_faqs')->nullable()->after('seo_content');
                }
            });
        }

        // Festivals — add slug + SEO fields
        if (Schema::hasTable('festivals')) {
            Schema::table('festivals', function (Blueprint $table) {
                if (!Schema::hasColumn('festivals', 'slug')) {
                    $table->string('slug', 255)->nullable()->after('title');
                }
                if (!Schema::hasColumn('festivals', 'seo_title')) {
                    $table->string('seo_title', 255)->nullable()->after('status');
                }
                if (!Schema::hasColumn('festivals', 'seo_description')) {
                    $table->text('seo_description')->nullable()->after('seo_title');
                }
                if (!Schema::hasColumn('festivals', 'seo_content')) {
                    $table->longText('seo_content')->nullable()->after('seo_description');
                }
                if (!Schema::hasColumn('festivals', 'seo_faqs')) {
                    $table->json('seo_faqs')->nullable()->after('seo_content');
                }
            });
        }

        // Blogs — add SEO enhancement fields
        if (Schema::hasTable('blogs')) {
            Schema::table('blogs', function (Blueprint $table) {
                if (!Schema::hasColumn('blogs', 'focus_keyword')) {
                    $table->string('focus_keyword', 255)->nullable();
                }
                if (!Schema::hasColumn('blogs', 'seo_title')) {
                    $table->string('seo_title', 255)->nullable();
                }
                if (!Schema::hasColumn('blogs', 'seo_description')) {
                    $table->text('seo_description')->nullable();
                }
                if (!Schema::hasColumn('blogs', 'og_image')) {
                    $table->string('og_image', 500)->nullable();
                }
                if (!Schema::hasColumn('blogs', 'blog_category')) {
                    $table->string('blog_category', 100)->nullable();
                }
                if (!Schema::hasColumn('blogs', 'author_name')) {
                    $table->string('author_name', 255)->nullable();
                }
                if (!Schema::hasColumn('blogs', 'faq_schema')) {
                    $table->json('faq_schema')->nullable();
                }
                if (!Schema::hasColumn('blogs', 'read_time')) {
                    $table->integer('read_time')->nullable();
                }
                if (!Schema::hasColumn('blogs', 'related_keywords')) {
                    $table->text('related_keywords')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $seoColumns = ['seo_title', 'seo_description', 'seo_content', 'seo_faqs', 'seo_image'];
        $blogSeoColumns = ['focus_keyword', 'seo_title', 'seo_description', 'og_image', 'blog_category', 'author_name', 'faq_schema', 'read_time', 'related_keywords'];

        if (Schema::hasTable('business_category')) {
            Schema::table('business_category', function (Blueprint $table) use ($seoColumns) {
                $table->dropColumn(array_intersect($seoColumns, Schema::getColumnListing('business_category')));
            });
        }

        if (Schema::hasTable('business_sub_category')) {
            Schema::table('business_sub_category', function (Blueprint $table) use ($seoColumns) {
                $cols = array_diff($seoColumns, ['seo_image']);
                $table->dropColumn(array_intersect($cols, Schema::getColumnListing('business_sub_category')));
            });
        }

        if (Schema::hasTable('business_types')) {
            Schema::table('business_types', function (Blueprint $table) use ($seoColumns) {
                $cols = array_diff($seoColumns, ['seo_image']);
                $table->dropColumn(array_intersect($cols, Schema::getColumnListing('business_types')));
            });
        }

        if (Schema::hasTable('festivals')) {
            Schema::table('festivals', function (Blueprint $table) {
                $cols = ['slug', 'seo_title', 'seo_description', 'seo_content', 'seo_faqs'];
                $table->dropColumn(array_intersect($cols, Schema::getColumnListing('festivals')));
            });
        }

        if (Schema::hasTable('blogs')) {
            Schema::table('blogs', function (Blueprint $table) use ($blogSeoColumns) {
                $table->dropColumn(array_intersect($blogSeoColumns, Schema::getColumnListing('blogs')));
            });
        }
    }
};
