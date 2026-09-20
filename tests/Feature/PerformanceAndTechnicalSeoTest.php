<?php

namespace Tests\Feature;

use App\Enums\ArticleStatus;
use App\Enums\CourseStatus;
use App\Http\Controllers\Public\SitemapController;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceAndTechnicalSeoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test sitemap.xml returns valid XML and includes all canonical public pages.
     */
    public function test_sitemap_returns_valid_xml_with_canonical_public_routes(): void
    {
        Cache::forget('sitemap_xml');

        $category = CourseCategory::create([
            'name' => 'SEO Marketing',
            'slug' => 'seo-marketing',
            'is_active' => true,
        ]);

        $publishedCourse = Course::create([
            'title' => 'Advanced SEO for Small Businesses',
            'slug' => 'advanced-seo-for-small-businesses',
            'short_description' => 'A practical SEO guide for small business founders.',
            'course_category_id' => $category->id,
            'price' => 4999.00,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $draftCourse = Course::create([
            'title' => 'Secret Draft Course',
            'slug' => 'secret-draft-course',
            'short_description' => 'Unpublished internal draft.',
            'course_category_id' => $category->id,
            'price' => 1999.00,
            'status' => CourseStatus::DRAFT,
            'access_validity_days' => 365,
        ]);

        $author = User::factory()->create(['role' => 'admin']);
        $articleCategory = ArticleCategory::create(['name' => 'SEO Strategy', 'slug' => 'seo-strategy', 'is_active' => true]);

        $publishedArticle = Article::create([
            'title' => 'How to Rank Local Businesses on Google',
            'slug' => 'how-to-rank-local-businesses-on-google',
            'author_id' => $author->id,
            'category_id' => $articleCategory->id,
            'status' => ArticleStatus::PUBLISHED,
            'content' => 'Comprehensive SEO ranking guide for local business owners.',
            'published_at' => now(),
        ]);

        $draftArticle = Article::create([
            'title' => 'Unpublished Blog Draft',
            'slug' => 'unpublished-blog-draft',
            'author_id' => $author->id,
            'category_id' => $articleCategory->id,
            'status' => ArticleStatus::DRAFT,
            'content' => 'Internal draft content.',
            'published_at' => null,
        ]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/xml; charset=utf-8');

        $content = $response->getContent();

        // Must be valid XML
        $xml = simplexml_load_string($content);
        $this->assertNotFalse($xml, 'sitemap.xml is not valid XML');

        // Must include core discovery routes
        $this->assertStringContainsString(url('/'), $content);
        $this->assertStringContainsString(route('courses'), $content);
        $this->assertStringContainsString(route('blog.index'), $content);
        $this->assertStringContainsString(route('about'), $content);
        $this->assertStringContainsString(route('contact'), $content);

        // Must include published course and article
        $this->assertStringContainsString(route('courses.show', $publishedCourse->slug), $content);
        $this->assertStringContainsString(route('blog.show', $publishedArticle->slug), $content);

        // Must EXCLUDE draft course, draft article, and internal routes
        $this->assertStringNotContainsString('secret-draft-course', $content);
        $this->assertStringNotContainsString('unpublished-blog-draft', $content);
        $this->assertStringNotContainsString('/admin', $content);
        $this->assertStringNotContainsString('/student', $content);
        $this->assertStringNotContainsString('/checkout', $content);
    }

    /**
     * Test sitemap cache invalidation hook when course or article is saved.
     */
    public function test_sitemap_cache_invalidates_on_model_mutation(): void
    {
        Cache::put('sitemap_xml', '<cached-xml></cached-xml>', 3600);
        $this->assertTrue(Cache::has('sitemap_xml'));

        $category = CourseCategory::create(['name' => 'Ads', 'slug' => 'ads', 'is_active' => true]);

        // Creating course must flush cache
        $course = Course::create([
            'title' => 'PPC Mastery',
            'slug' => 'ppc-mastery',
            'short_description' => 'Master PPC advertising campaigns.',
            'course_category_id' => $category->id,
            'price' => 3999.00,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->assertFalse(Cache::has('sitemap_xml'));

        // Manually re-cache and test update
        Cache::put('sitemap_xml', '<cached-xml></cached-xml>', 3600);
        $course->update(['title' => 'PPC Mastery 2.0']);
        $this->assertFalse(Cache::has('sitemap_xml'));

        // Explicit SitemapController::clearCache()
        Cache::put('sitemap_xml', '<cached-xml></cached-xml>', 3600);
        SitemapController::clearCache();
        $this->assertFalse(Cache::has('sitemap_xml'));
    }

    /**
     * Test robots.txt file exists and blocks internal/private surfaces.
     */
    public function test_robots_txt_protects_internal_paths_and_links_sitemap(): void
    {
        $robotsPath = public_path('robots.txt');
        $this->assertFileExists($robotsPath);

        $content = file_get_contents($robotsPath);

        $this->assertStringContainsString('Disallow: /admin/', $content);
        $this->assertStringContainsString('Disallow: /student/', $content);
        $this->assertStringContainsString('Disallow: /checkout/', $content);
        $this->assertStringContainsString('Disallow: /cart/', $content);
        $this->assertStringContainsString('Disallow: /payment/', $content);
        $this->assertStringContainsString('Disallow: /webhooks/', $content);
        $this->assertStringContainsString('Disallow: /login', $content);
        $this->assertStringContainsString('Disallow: /register', $content);
        $this->assertStringContainsString('Disallow: /password/', $content);
        $this->assertStringContainsString('Disallow: /marketing/', $content);
        $this->assertStringContainsString('Disallow: /whatsapp/', $content);
        $this->assertStringContainsString('Sitemap: https://marketianmind.com/sitemap.xml', $content);
    }

    /**
     * Test home page renders valid metadata, Open Graph, and JSON-LD schemas.
     */
    public function test_home_page_metadata_and_structured_data(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);

        // Title and description
        $response->assertSee('<title>', false);
        $response->assertSee('Marketian Mind', false);
        $response->assertSee('<meta name="description"', false);
        $response->assertSee('<link rel="canonical"', false);

        // Open Graph
        $response->assertSee('<meta property="og:title"', false);
        $response->assertSee('<meta property="og:description"', false);
        $response->assertSee('<meta property="og:type" content="website">', false);

        // Schema.org structured data
        $response->assertSee('"@type": "Organization"', false);
        $response->assertSee('"@type": "WebSite"', false);
        $response->assertSee('"potentialAction": {', false);
    }

    /**
     * Test public course catalog page renders valid SEO metadata and BreadcrumbList schema.
     */
    public function test_course_catalog_metadata_and_breadcrumbs(): void
    {
        $category = CourseCategory::create(['name' => 'Social Media', 'slug' => 'social-media', 'is_active' => true]);

        Course::create([
            'title' => 'Instagram Growth Strategies',
            'slug' => 'instagram-growth-strategies',
            'short_description' => 'Grow organic reach for your local business.',
            'course_category_id' => $category->id,
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $response = $this->get(route('courses'));

        $response->assertStatus(200);
        $response->assertSee('<title>', false);
        $response->assertSee('Online Marketing Courses for Business Owners', false);
        $response->assertSee(route('courses'), false);
        $response->assertSee('"@type": "BreadcrumbList"', false);
    }

    /**
     * Test course details page renders valid Course schema with price and currency.
     */
    public function test_course_detail_page_renders_course_schema(): void
    {
        $category = CourseCategory::create(['name' => 'Foundations', 'slug' => 'foundations', 'is_active' => true]);

        $course = Course::create([
            'title' => 'Digital Marketing Fundamentals for Founders',
            'slug' => 'digital-marketing-fundamentals-for-founders',
            'course_category_id' => $category->id,
            'price' => 5999.00,
            'discount_price' => 3999.00,
            'short_description' => 'Comprehensive primer for busy founders.',
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $response = $this->get(route('courses.show', $course->slug));

        $response->assertStatus(200);
        $response->assertSee('"@type": "Course"', false);
        $response->assertSee('"@type": "Offer"', false);
        $response->assertSee('"priceCurrency": "INR"', false);
        $response->assertSee('"price": "3999.00"', false);
        $response->assertSee('"@type": "BreadcrumbList"', false);
    }

    /**
     * Test About and Contact pages render valid SEO metadata.
     */
    public function test_about_and_contact_pages_render_metadata(): void
    {
        $aboutResponse = $this->get(route('about'));
        $aboutResponse->assertStatus(200);
        $aboutResponse->assertSee('<title>', false);
        $aboutResponse->assertSee('About Us', false);
        $aboutResponse->assertSee('"@type": "Organization"', false);
        $aboutResponse->assertSee('"@type": "BreadcrumbList"', false);

        $contactResponse = $this->get(route('contact'));
        $contactResponse->assertStatus(200);
        $contactResponse->assertSee('<title>', false);
        $contactResponse->assertSee('Contact Us', false);
        $contactResponse->assertSee('"@type": "BreadcrumbList"', false);
    }

    /**
     * Test private and authenticated routes enforce noindex, nofollow.
     */
    public function test_private_routes_enforce_noindex_nofollow(): void
    {
        $student = User::factory()->create(['role' => 'student']);

        // 1. Guest login page
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // 2. Student dashboard
        $dashboardResponse = $this->actingAs($student)->get(route('student.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        // 3. Student courses page
        $studentCoursesResponse = $this->actingAs($student)->get(route('student.courses'));
        $studentCoursesResponse->assertStatus(200);
        $studentCoursesResponse->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    /**
     * Test public course catalog query efficiency and prevention of N+1 queries.
     */
    public function test_course_catalog_query_efficiency(): void
    {
        $category = CourseCategory::create(['name' => 'Strategy', 'slug' => 'strategy', 'is_active' => true]);

        // Create 10 published courses with category
        for ($i = 1; $i <= 10; $i++) {
            Course::create([
                'title' => "Growth Course {$i}",
                'slug' => "growth-course-{$i}",
                'short_description' => "Description for growth course {$i}.",
                'course_category_id' => $category->id,
                'price' => 1000.00 * $i,
                'status' => CourseStatus::PUBLISHED,
                'access_validity_days' => 365,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->get(route('courses'));
        $response->assertStatus(200);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // With eager loading of category, counts, and reviews, query count should remain small and constant (< 10)
        $this->assertLessThanOrEqual(10, $queryCount, "Query count {$queryCount} exceeded acceptable threshold on course catalog.");
    }

    /**
     * Test offer resolution and eager loading efficiency.
     */
    public function test_pricing_service_and_eager_loading_correctness(): void
    {
        $category = CourseCategory::create(['name' => 'Offers', 'slug' => 'offers', 'is_active' => true]);
        $course = Course::create([
            'title' => 'Offer Course',
            'slug' => 'offer-course',
            'short_description' => 'Course with promo offer.',
            'course_category_id' => $category->id,
            'price' => 5000.00,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $offer = \App\Models\Offer::create([
            'name' => 'Diwali Discount',
            'discount_type' => \App\Enums\OfferDiscountType::PERCENTAGE,
            'discount_value' => 20.00,
            'is_active' => true,
        ]);
        \App\Models\OfferProduct::create([
            'offer_id' => $offer->id,
            'product_type' => 'course',
            'product_id' => $course->id,
        ]);

        $pricingService = app(\App\Services\PricingService::class);
        $winningOffer = $pricingService->getWinningOffer($course);

        $this->assertNotNull($winningOffer);
        $this->assertEquals($offer->id, $winningOffer->id);

        // Test eager loaded resolution
        $loadedCourse = Course::with(['offers' => function ($q) {
            $q->currentlyValid()->orderByDesc('priority')->orderByDesc('discount_value')->orderByDesc('id');
        }])->find($course->id);

        $this->assertTrue($loadedCourse->relationLoaded('offers'));
        $this->assertNotNull($loadedCourse->currentOffer());
        $this->assertEquals($offer->id, $loadedCourse->currentOffer()->id);
    }
}
