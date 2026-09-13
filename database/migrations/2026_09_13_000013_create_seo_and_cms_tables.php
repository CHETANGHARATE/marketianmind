<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Article Categories
        if (!Schema::hasTable('article_categories')) {
            Schema::create('article_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('slug', 120)->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
            });
        }

        // 2. Articles
        if (!Schema::hasTable('articles')) {
            Schema::create('articles', function (Blueprint $table) {
                $table->id();
                $table->string('title', 255);
                $table->string('slug', 255)->unique();
                $table->text('excerpt')->nullable();
                $table->longText('content');
                $table->string('featured_image', 500)->nullable();
                $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('category_id')->nullable()->constrained('article_categories')->nullOnDelete();
                $table->string('status', 20)->default('draft')->index(); // draft, published, archived
                $table->timestamp('published_at')->nullable()->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->unsignedSmallInteger('reading_time_minutes')->default(1);
                $table->timestamps();

                $table->index(['status', 'published_at']);
            });
        }

        // 3. Article Tags
        if (!Schema::hasTable('article_tags')) {
            Schema::create('article_tags', function (Blueprint $table) {
                $table->id();
                $table->string('name', 50);
                $table->string('slug', 60)->unique();
                $table->timestamps();
            });
        }

        // 4. Article - Tag Pivot
        if (!Schema::hasTable('article_tag')) {
            Schema::create('article_tag', function (Blueprint $table) {
                $table->foreignId('article_id')->constrained('articles')->cascadeOnDelete();
                $table->foreignId('tag_id')->constrained('article_tags')->cascadeOnDelete();
                $table->primary(['article_id', 'tag_id']);
            });
        }

        // 5. Polymorphic SEO Metadata (Course, Bundle, Article, etc.)
        if (!Schema::hasTable('seo_metas')) {
            Schema::create('seo_metas', function (Blueprint $table) {
                $table->id();
                $table->string('seoable_type', 150);
                $table->unsignedBigInteger('seoable_id');
                $table->string('meta_title', 255)->nullable();
                $table->text('meta_description')->nullable();
                $table->string('canonical_url', 500)->nullable();
                $table->string('robots', 50)->default('index, follow');
                $table->string('og_title', 255)->nullable();
                $table->text('og_description')->nullable();
                $table->string('og_image', 500)->nullable();
                $table->string('twitter_card', 50)->default('summary_large_image');
                $table->string('schema_type', 50)->nullable();
                $table->timestamps();

                $table->index(['seoable_type', 'seoable_id']);
            });
        }

        // 6. 301 Slug Redirects
        if (!Schema::hasTable('slug_redirects')) {
            Schema::create('slug_redirects', function (Blueprint $table) {
                $table->id();
                $table->string('old_path', 255)->unique();
                $table->string('new_path', 255);
                $table->unsignedSmallInteger('status_code')->default(301);
                $table->unsignedInteger('hits')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slug_redirects');
        Schema::dropIfExists('seo_metas');
        Schema::dropIfExists('article_tag');
        Schema::dropIfExists('article_tags');
        Schema::dropIfExists('articles');
        Schema::dropIfExists('article_categories');
    }
};
