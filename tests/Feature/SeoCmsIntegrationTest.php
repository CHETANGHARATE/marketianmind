<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\CourseStatus;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\ArticleTag;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\SeoMeta;
use App\Models\SlugRedirect;
use App\Models\User;
use App\Services\ContentSanitizerService;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SeoCmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected ArticleCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@marketianmind.test',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student@marketianmind.test',
        ]);

        $this->category = ArticleCategory::create([
            'name' => 'Lead Generation',
            'slug' => 'lead-generation',
            'description' => 'Tactics for capturing and qualifying high-intent buyers.',
            'is_active' => true,
        ]);
    }

    public function test_seo_and_cms_database_tables_and_columns_exist(): void
    {
        $this->assertTrue(Schema::hasTable('article_categories'));
        $this->assertTrue(Schema::hasColumns('article_categories', ['id', 'name', 'slug', 'description', 'is_active', 'created_at']));

        $this->assertTrue(Schema::hasTable('articles'));
        $this->assertTrue(Schema::hasColumns('articles', ['id', 'title', 'slug', 'excerpt', 'content', 'featured_image', 'author_id', 'category_id', 'status', 'published_at', 'is_featured', 'reading_time_minutes']));

        $this->assertTrue(Schema::hasTable('article_tags'));
        $this->assertTrue(Schema::hasColumns('article_tags', ['id', 'name', 'slug']));

        $this->assertTrue(Schema::hasTable('article_tag'));
        $this->assertTrue(Schema::hasColumns('article_tag', ['article_id', 'tag_id']));

        $this->assertTrue(Schema::hasTable('seo_metas'));
        $this->assertTrue(Schema::hasColumns('seo_metas', ['id', 'seoable_type', 'seoable_id', 'meta_title', 'meta_description', 'canonical_url', 'robots', 'og_title', 'og_description', 'og_image', 'twitter_card']));

        $this->assertTrue(Schema::hasTable('slug_redirects'));
        $this->assertTrue(Schema::hasColumns('slug_redirects', ['id', 'old_path', 'new_path', 'status_code', 'hits']));
    }

    public function test_article_model_has_relationships_to_author_category_tags_and_seo(): void
    {
        $article = Article::create([
            'title' => 'Mastering Meta Ads in 2026',
            'slug' => 'mastering-meta-ads-2026',
            'content' => '<p>Learn performance marketing fundamentals.</p>',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $tag = ArticleTag::create(['name' => 'Paid Media', 'slug' => 'paid-media']);
        $article->tags()->attach($tag->id);

        $article->seo()->create([
            'meta_title' => 'Meta Ads Mastery | Marketian Mind',
            'meta_description' => 'Comprehensive playbook for Meta advertising.',
            'canonical_url' => 'https://marketianmind.com/blog/mastering-meta-ads-2026',
        ]);

        $this->assertInstanceOf(User::class, $article->author);
        $this->assertEquals($this->admin->id, $article->author->id);

        $this->assertInstanceOf(ArticleCategory::class, $article->category);
        $this->assertEquals($this->category->id, $article->category->id);

        $this->assertCount(1, $article->tags);
        $this->assertEquals('Paid Media', $article->tags->first()->name);

        $this->assertInstanceOf(SeoMeta::class, $article->seo);
        $this->assertEquals('Meta Ads Mastery | Marketian Mind', $article->seo->meta_title);
    }

    public function test_article_published_scope_filters_draft_and_future_articles(): void
    {
        $published = Article::create([
            'title' => 'Published Article',
            'slug' => 'published-article',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subHour(),
        ]);

        $draft = Article::create([
            'title' => 'Draft Article',
            'slug' => 'draft-article',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::DRAFT->value,
        ]);

        $future = Article::create([
            'title' => 'Scheduled Article',
            'slug' => 'scheduled-article',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->addDay(),
        ]);

        $publishedArticles = Article::published()->get();

        $this->assertTrue($publishedArticles->contains($published));
        $this->assertFalse($publishedArticles->contains($draft));
        $this->assertFalse($publishedArticles->contains($future));
    }

    public function test_content_sanitizer_removes_script_tags(): void
    {
        $sanitizer = app(ContentSanitizerService::class);
        $raw = '<p>Normal text</p><script>alert("XSS")</script><script src="https://evil.com/payload.js"></script>';

        $clean = $sanitizer->sanitize($raw);

        $this->assertStringNotContainsString('<script', $clean);
        $this->assertStringNotContainsString('alert', $clean);
        $this->assertStringContainsString('<p>Normal text</p>', $clean);
    }

    public function test_content_sanitizer_removes_iframe_and_object_tags(): void
    {
        $sanitizer = app(ContentSanitizerService::class);
        $raw = '<p>Safe</p><iframe src="https://attacker.com/cookie-stealer"></iframe><object data="bad.swf"></object>';

        $clean = $sanitizer->sanitize($raw);

        $this->assertStringNotContainsString('<iframe', $clean);
        $this->assertStringNotContainsString('<object', $clean);
        $this->assertStringContainsString('Safe', $clean);
    }

    public function test_content_sanitizer_removes_inline_event_handlers(): void
    {
        $sanitizer = app(ContentSanitizerService::class);
        $raw = '<img src="photo.jpg" onerror="alert(1)" onload="evil()"><a href="/about" onclick="steal()">Click</a>';

        $clean = $sanitizer->sanitize($raw);

        $this->assertStringNotContainsString('onerror', $clean);
        $this->assertStringNotContainsString('onload', $clean);
        $this->assertStringNotContainsString('onclick', $clean);
        $this->assertStringContainsString('src="photo.jpg"', $clean);
        $this->assertStringContainsString('href="/about"', $clean);
    }

    public function test_content_sanitizer_neutralizes_javascript_uri_schemes(): void
    {
        $sanitizer = app(ContentSanitizerService::class);
        $raw = '<a href="javascript:alert(1)">Click Me</a><a href="JAVASCRIPT:alert(2)">Click 2</a>';

        $clean = $sanitizer->sanitize($raw);

        $this->assertStringNotContainsString('javascript:', strtolower($clean));
        $this->assertStringNotContainsString('alert', $clean);
    }

    public function test_content_sanitizer_preserves_safe_html_formatting_and_adds_noopener_to_links(): void
    {
        $sanitizer = app(ContentSanitizerService::class);
        $raw = '<h2>Subheading</h2><p>Here is <strong>bold</strong> and <em>italic</em> with an <a href="https://google.com" target="_blank">External Link</a>.</p>';

        $clean = $sanitizer->sanitize($raw);

        $this->assertStringContainsString('<h2>Subheading</h2>', $clean);
        $this->assertStringContainsString('<strong>bold</strong>', $clean);
        $this->assertStringContainsString('<em>italic</em>', $clean);
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }

    public function test_public_blog_index_displays_published_articles(): void
    {
        Article::create([
            'title' => 'Top 5 Conversion Funnel Strategies',
            'slug' => 'top-5-conversion-funnels',
            'content' => '<p>Detailed guide on conversion funnels.</p>',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.index'));

        $response->assertStatus(200);
        $response->assertSee('Top 5 Conversion Funnel Strategies');
        $response->assertSee('Lead Generation');
    }

    public function test_public_blog_index_filters_by_category(): void
    {
        $otherCategory = ArticleCategory::create([
            'name' => 'Email Marketing',
            'slug' => 'email-marketing',
        ]);

        $art1 = Article::create([
            'title' => 'B2B Lead Nurturing',
            'slug' => 'b2b-lead-nurturing',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $art2 = Article::create([
            'title' => 'Email Drip Campaign Blueprint',
            'slug' => 'email-drip-blueprint',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'category_id' => $otherCategory->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.index', ['category' => 'lead-generation']));

        $response->assertStatus(200);
        $response->assertSee('B2B Lead Nurturing');
        $response->assertDontSee('Email Drip Campaign Blueprint');
    }

    public function test_public_blog_index_filters_by_tag(): void
    {
        $tag = ArticleTag::create(['name' => 'Copywriting', 'slug' => 'copywriting']);

        $art1 = Article::create([
            'title' => 'High-Converting Headlines',
            'slug' => 'high-converting-headlines',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);
        $art1->tags()->attach($tag->id);

        $art2 = Article::create([
            'title' => 'SEO Technical Audit',
            'slug' => 'seo-technical-audit',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.index', ['tag' => 'copywriting']));

        $response->assertStatus(200);
        $response->assertSee('High-Converting Headlines');
        $response->assertDontSee('SEO Technical Audit');
    }

    public function test_public_blog_index_searches_by_keyword(): void
    {
        Article::create([
            'title' => 'Retargeting on Facebook and Instagram',
            'slug' => 'retargeting-meta',
            'excerpt' => 'Advanced retargeting tactics',
            'content' => 'Full text',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        Article::create([
            'title' => 'Cold Email Outreach Mastery',
            'slug' => 'cold-email-mastery',
            'excerpt' => 'Outreach tactics',
            'content' => 'Full text',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.index', ['q' => 'Retargeting']));

        $response->assertStatus(200);
        $response->assertSee('Retargeting on Facebook and Instagram');
        $response->assertDontSee('Cold Email Outreach Mastery');
    }

    public function test_public_blog_index_paginates_articles(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            Article::create([
                'title' => "Growth Article {$i}",
                'slug' => "growth-article-{$i}",
                'content' => 'Content',
                'author_id' => $this->admin->id,
                'status' => ArticleStatus::PUBLISHED->value,
                'published_at' => now()->subMinutes($i),
            ]);
        }

        $response = $this->get(route('blog.index'));
        $response->assertStatus(200);
        $response->assertSee('Growth Article 1');

        $responsePage2 = $this->get(route('blog.index', ['page' => 2]));
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('Growth Article 12');
    }

    public function test_public_blog_show_renders_published_article_with_metadata(): void
    {
        $article = Article::create([
            'title' => 'The Complete ROI Marketing Blueprint',
            'slug' => 'complete-roi-marketing-blueprint',
            'content' => '<p>Practical steps to calculate Customer Lifetime Value and CAC.</p>',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDays(2),
        ]);

        $response = $this->get(route('blog.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee('The Complete ROI Marketing Blueprint');
        $response->assertSee('Customer Lifetime Value and CAC');
        $response->assertSee('<meta name="robots" content="index, follow">', false);
    }

    public function test_public_blog_show_returns_404_for_draft_article_for_guests(): void
    {
        $draft = Article::create([
            'title' => 'Unpublished Secret Tactics',
            'slug' => 'unpublished-secret-tactics',
            'content' => 'Top secret content.',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::DRAFT->value,
        ]);

        $response = $this->get(route('blog.show', $draft->slug));
        $response->assertStatus(404);
    }

    public function test_public_blog_show_returns_404_for_archived_article(): void
    {
        $archived = Article::create([
            'title' => 'Deprecated 2021 Strategy',
            'slug' => 'deprecated-2021-strategy',
            'content' => 'Old content.',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::ARCHIVED->value,
        ]);

        $response = $this->get(route('blog.show', $archived->slug));
        $response->assertStatus(404);
    }

    public function test_public_blog_show_allows_admin_to_preview_draft_article(): void
    {
        $draft = Article::create([
            'title' => 'Admin Preview Draft',
            'slug' => 'admin-preview-draft',
            'content' => 'Previewing draft content.',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::DRAFT->value,
        ]);

        $response = $this->actingAs($this->admin)->get(route('blog.show', $draft->slug));

        $response->assertStatus(200);
        $response->assertSee('Admin Preview Draft');
    }

    public function test_public_blog_show_displays_reading_time_and_author_details(): void
    {
        $article = Article::create([
            'title' => 'Reading Time Guide',
            'slug' => 'reading-time-guide',
            'content' => str_repeat('word ', 600), // ~600 words = 3 mins
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee($this->admin->name);
        $response->assertSee('3 min read');
    }

    public function test_public_blog_show_displays_breadcrumbs(): void
    {
        $article = Article::create([
            'title' => 'Breadcrumb Navigation Post',
            'slug' => 'breadcrumb-nav-post',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee('Home');
        $response->assertSee('Blog');
        $response->assertSee($this->category->name);
    }

    public function test_public_blog_show_renders_internal_course_recommendations(): void
    {
        $cat = CourseCategory::create(['name' => 'Marketing Basics', 'slug' => 'marketing-basics']);
        $course = Course::create([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery',
            'short_description' => 'Comprehensive masterclass',
            'description' => 'Full description',
            'course_category_id' => $cat->id,
            'price' => 4999.00,
            'status' => CourseStatus::PUBLISHED->value,
        ]);

        $article = Article::create([
            'title' => 'Recommended Course Test',
            'slug' => 'recommended-course-test',
            'content' => 'Content',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('blog.show', $article->slug));

        $response->assertStatus(200);
        $response->assertSee('Digital Marketing Mastery');
        $response->assertSee('₹4,999');
    }

    public function test_slug_redirect_returns_301_and_increments_hits_counter(): void
    {
        $redirect = SlugRedirect::create([
            'old_path' => '/blog/old-outdated-slug',
            'new_path' => '/blog/new-fresh-slug',
            'status_code' => 301,
            'hits' => 0,
        ]);

        $response = $this->get('/blog/old-outdated-slug');

        $response->assertStatus(301);
        $response->assertRedirect('/blog/new-fresh-slug');

        $this->assertEquals(1, $redirect->fresh()->hits);
    }

    public function test_seo_service_generates_standard_title_and_description(): void
    {
        $seoService = app(SeoService::class);

        $meta = $seoService->buildMeta([
            'title' => 'SEO Best Practices',
            'description' => 'Actionable search engine optimization steps.',
        ]);

        $this->assertStringContainsString('SEO Best Practices', $meta['title']);
        $this->assertStringContainsString('Marketian Mind', $meta['title']);
        $this->assertEquals('Actionable search engine optimization steps.', $meta['description']);
    }

    public function test_seo_service_canonical_url_strips_tracking_parameters(): void
    {
        $seoService = app(SeoService::class);

        $dirtyUrl = 'https://marketianmind.com/blog/lead-gen?utm_source=facebook&utm_medium=cpc&fbclid=123456&ref=partner';
        $clean = $seoService->generateCanonical($dirtyUrl);

        $this->assertEquals('https://marketianmind.com/blog/lead-gen', $clean);
    }

    public function test_seo_service_generates_open_graph_and_twitter_cards(): void
    {
        $seoService = app(SeoService::class);

        $meta = $seoService->buildMeta([
            'title' => 'Social Sharing Guide',
            'description' => 'Social meta tags guide.',
            'og_image' => 'https://marketianmind.com/images/share.jpg',
            'og_type' => 'article',
        ]);

        $this->assertStringContainsString('Social Sharing Guide', $meta['og_title']);
        $this->assertEquals('article', $meta['og_type']);
        $this->assertEquals('https://marketianmind.com/images/share.jpg', $meta['og_image']);
        $this->assertEquals('summary_large_image', $meta['twitter_card']);
    }

    public function test_seo_service_generates_organization_schema(): void
    {
        $seoService = app(SeoService::class);
        $schema = $seoService->buildOrganizationSchema();

        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Organization', $schema['@type']);
        $this->assertEquals('Marketian Mind', $schema['name']);
        $this->assertArrayHasKey('sameAs', $schema);
    }

    public function test_seo_service_generates_website_schema(): void
    {
        $seoService = app(SeoService::class);
        $schema = $seoService->buildWebSiteSchema();

        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('WebSite', $schema['@type']);
        $this->assertEquals('Marketian Mind', $schema['name']);
        $this->assertArrayHasKey('potentialAction', $schema);
    }

    public function test_seo_service_generates_course_schema_for_course_pages(): void
    {
        $cat = CourseCategory::create(['name' => 'Ads', 'slug' => 'ads']);
        $course = Course::create([
            'title' => 'Google Ads Mastery',
            'slug' => 'google-ads-mastery',
            'short_description' => 'PPC advertising course',
            'description' => 'Full curriculum',
            'course_category_id' => $cat->id,
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED->value,
        ]);

        $seoService = app(SeoService::class);
        $schema = $seoService->buildCourseSchema($course);

        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Course', $schema['@type']);
        $this->assertEquals('Google Ads Mastery', $schema['name']);
        $this->assertEquals('INR', $schema['offers']['priceCurrency']);
        $this->assertEquals('2999.00', $schema['offers']['price']);
    }

    public function test_seo_service_generates_article_schema_for_blog_posts(): void
    {
        $article = Article::create([
            'title' => 'Schema Markup Strategy',
            'slug' => 'schema-markup-strategy',
            'excerpt' => 'Structured data overview',
            'content' => '<p>Article body</p>',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $seoService = app(SeoService::class);
        $schema = $seoService->buildArticleSchema($article);

        $this->assertEquals('https://schema.org', $schema['@context']);
        $this->assertEquals('Article', $schema['@type']);
        $this->assertEquals('Schema Markup Strategy', $schema['headline']);
        $this->assertEquals($this->admin->name, $schema['author']['name']);
        $this->assertEquals('Marketian Mind', $schema['publisher']['name']);
    }

    public function test_blade_seo_component_renders_canonical_and_meta_tags(): void
    {
        $rendered = (string) $this->blade('<x-seo-meta title="Test Page Title" description="Test meta description" />');

        $this->assertStringContainsString('<title>Test Page Title | Marketian Mind</title>', $rendered);
        $this->assertStringContainsString('<meta name="description" content="Test meta description">', $rendered);
        $this->assertStringContainsString('<meta name="robots" content="index, follow">', $rendered);
        $this->assertStringContainsString('<link rel="canonical"', $rendered);
        $this->assertStringContainsString('<meta property="og:title"', $rendered);
    }

    public function test_private_routes_emit_noindex_robots_directive(): void
    {
        // Admin route should output noindex
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // Student route should output noindex
        $studentResponse = $this->actingAs($this->student)->get(route('student.dashboard'));
        $studentResponse->assertStatus(200);
        $studentResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_dynamic_sitemap_returns_valid_xml_with_proper_headers(): void
    {
        Cache::forget('sitemap_xml');

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $this->assertStringContainsString('text/xml', $response->headers->get('Content-Type'));
        $response->assertSee('<?xml version="1.0" encoding="UTF-8"?>', false);
        $response->assertSee('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', false);
    }

    public function test_sitemap_includes_public_courses_bundles_and_published_articles(): void
    {
        Cache::forget('sitemap_xml');

        $cat = CourseCategory::create(['name' => 'Sitemap Cat', 'slug' => 'sitemap-cat']);
        $course = Course::create([
            'title' => 'Sitemap Public Course',
            'slug' => 'sitemap-public-course',
            'short_description' => 'Short course overview',
            'description' => 'Desc',
            'course_category_id' => $cat->id,
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED->value,
        ]);

        $article = Article::create([
            'title' => 'Sitemap Public Article',
            'slug' => 'sitemap-public-article',
            'content' => 'Body',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::PUBLISHED->value,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $response->assertSee(route('courses.show', $course->slug), false);
        $response->assertSee(route('blog.show', $article->slug), false);
    }

    public function test_sitemap_excludes_draft_articles_and_private_urls(): void
    {
        Cache::forget('sitemap_xml');

        $draft = Article::create([
            'title' => 'Draft Excluded Article',
            'slug' => 'draft-excluded-article',
            'content' => 'Body',
            'author_id' => $this->admin->id,
            'status' => ArticleStatus::DRAFT->value,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertStatus(200);
        $response->assertDontSee(route('blog.show', $draft->slug), false);
        $response->assertDontSee('/admin', false);
        $response->assertDontSee('/student', false);
    }

    public function test_robots_txt_disallows_private_routes_and_points_to_sitemap(): void
    {
        $robotsContent = file_get_contents(public_path('robots.txt'));

        $this->assertStringContainsString('Disallow: /admin/', $robotsContent);
        $this->assertStringContainsString('Disallow: /student/', $robotsContent);
        $this->assertStringContainsString('Disallow: /checkout/', $robotsContent);
        $this->assertStringContainsString('Sitemap:', $robotsContent);
    }

    public function test_admin_article_crud_with_seo_meta_slug_redirect_and_audit_logging(): void
    {
        // 1. Admin Index
        $indexResponse = $this->actingAs($this->admin)->get(route('admin.articles.index'));
        $indexResponse->assertStatus(200);

        // 2. Admin Store
        $storeResponse = $this->actingAs($this->admin)->post(route('admin.articles.store'), [
            'title' => 'Admin Created Article',
            'slug' => 'admin-created-article',
            'excerpt' => 'Short summary for test',
            'content' => '<p>High quality content with <script>evil()</script></p>',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'tags' => 'growth, analytics',
            'meta_title' => 'SEO Title Override',
            'meta_description' => 'SEO Meta Desc',
            'canonical_url' => 'https://marketianmind.com/blog/admin-created-article',
            'robots' => 'index, follow',
        ]);

        $storeResponse->assertRedirect(route('admin.articles.index'));
        $article = Article::where('slug', 'admin-created-article')->first();
        $this->assertNotNull($article);
        $this->assertEquals('Admin Created Article', $article->title);
        $this->assertEquals(ArticleStatus::PUBLISHED, $article->status);
        $this->assertCount(2, $article->tags);
        $this->assertNotNull($article->seo);
        $this->assertEquals('SEO Title Override', $article->seo->meta_title);

        // 3. Admin Update with Slug Change -> verifies 301 SlugRedirect created
        $updateResponse = $this->actingAs($this->admin)->put(route('admin.articles.update', $article), [
            'title' => 'Admin Updated Article Title',
            'slug' => 'admin-renamed-article-slug',
            'excerpt' => 'Updated summary',
            'content' => '<p>Updated content</p>',
            'author_id' => $this->admin->id,
            'category_id' => $this->category->id,
            'status' => 'published',
            'meta_title' => 'Updated SEO Title',
        ]);

        $updateResponse->assertRedirect(route('admin.articles.index'));
        $this->assertEquals('admin-renamed-article-slug', $article->fresh()->slug);

        $redirect = SlugRedirect::where('old_path', '/blog/admin-created-article')->first();
        $this->assertNotNull($redirect);
        $this->assertEquals('/blog/admin-renamed-article-slug', $redirect->new_path);
        $this->assertEquals(301, $redirect->status_code);

        // 4. Admin Category CRUD
        $catResponse = $this->actingAs($this->admin)->post(route('admin.article-categories.store'), [
            'name' => 'Paid Search Ads',
            'slug' => 'paid-search-ads',
            'description' => 'Google Search and Bing Search campaigns.',
        ]);
        $catResponse->assertRedirect(route('admin.article-categories.index'));
        $this->assertDatabaseHas('article_categories', ['slug' => 'paid-search-ads']);

        // 5. Admin Delete
        $deleteResponse = $this->actingAs($this->admin)->delete(route('admin.articles.destroy', $article));
        $deleteResponse->assertRedirect(route('admin.articles.index'));
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
    }
}
