<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\CourseReviewStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedCourseSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected CourseCategory $categoryMarketing;
    protected CourseCategory $categorySales;
    protected CourseCategory $categorySocial;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->categoryMarketing = CourseCategory::create([
            'name' => 'Marketing Strategy',
            'slug' => 'marketing-strategy',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $this->categorySales = CourseCategory::create([
            'name' => 'B2B Sales Mastery',
            'slug' => 'sales-mastery',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $this->categorySocial = CourseCategory::create([
            'name' => 'Social Media Advertising',
            'slug' => 'social-advertising',
            'status' => CategoryStatus::ACTIVE,
        ]);
    }

    /**
     * TEST 1: Search by exact and partial title.
     */
    public function test_search_by_exact_and_partial_title_returns_matching_courses(): void
    {
        $c1 = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Advanced Growth Hacking 101',
            'slug' => 'growth-hacking-101',
            'short_description' => 'Scalable customer acquisition models.',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $c2 = Course::create([
            'course_category_id' => $this->categorySales->id,
            'title' => 'Cold Email Inbound Mastery',
            'slug' => 'cold-email-inbound',
            'short_description' => 'Generate client meetings without ads.',
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Search partial title
        $response = $this->get('/courses?q=Growth');
        $response->assertStatus(200);
        $response->assertSee('Advanced Growth Hacking 101');
        $response->assertDontSee('Cold Email Inbound Mastery');

        // Search other title
        $response2 = $this->get('/courses?q=Cold+Email');
        $response2->assertStatus(200);
        $response2->assertSee('Cold Email Inbound Mastery');
        $response2->assertDontSee('Advanced Growth Hacking 101');
    }

    /**
     * TEST 2: Search by short and long descriptions.
     */
    public function test_search_by_short_and_long_description_returns_matching_courses(): void
    {
        $c1 = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Digital Funnel Architecture',
            'slug' => 'digital-funnel-arch',
            'short_description' => 'Build high converting landing pages and nurture sequences.',
            'description' => 'Comprehensive walkthrough of lead magnet automation and retargeting.',
            'price' => 2499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $c2 = Course::create([
            'course_category_id' => $this->categorySales->id,
            'title' => 'Enterprise Prospecting',
            'slug' => 'enterprise-prospecting',
            'short_description' => 'B2B sales tactics for high ticket consultants.',
            'description' => 'Master telephone and LinkedIn outreach scripts.',
            'price' => 3499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Search keyword present in short_description
        $resShort = $this->get('/courses?q=nurture+sequences');
        $resShort->assertStatus(200);
        $resShort->assertSee('Digital Funnel Architecture');
        $resShort->assertDontSee('Enterprise Prospecting');

        // Search keyword present in long description
        $resLong = $this->get('/courses?q=retargeting');
        $resLong->assertStatus(200);
        $resLong->assertSee('Digital Funnel Architecture');
        $resLong->assertDontSee('Enterprise Prospecting');
    }

    /**
     * TEST 3: Search by instructor name.
     */
    public function test_search_by_instructor_name_returns_matching_courses(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Copywriting for Startups',
            'slug' => 'copywriting-for-startups',
            'short_description' => 'Write words that convert visitors into buyers.',
            'instructor_name' => 'Alexander Hamilton',
            'price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'PPC Mastery',
            'slug' => 'ppc-mastery',
            'short_description' => 'Google Search Ads for local businesses.',
            'instructor_name' => 'Eleanor Roosevelt',
            'price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $response = $this->get('/courses?q=Alexander');
        $response->assertStatus(200);
        $response->assertSee('Copywriting for Startups');
        $response->assertDontSee('PPC Mastery');
    }

    /**
     * TEST 4: Search by category name matches courses via relationship.
     */
    public function test_search_by_category_name_returns_matching_courses(): void
    {
        Course::create([
            'course_category_id' => $this->categorySocial->id,
            'title' => 'TikTok Organic Brand Playbook',
            'slug' => 'tiktok-brand-playbook',
            'short_description' => 'Viral short-form content strategies.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        Course::create([
            'course_category_id' => $this->categorySales->id,
            'title' => 'Closing High Ticket Retainers',
            'slug' => 'closing-high-ticket',
            'short_description' => 'Objection handling and pricing models.',
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Search with category name keyword 'Advertising'
        $response = $this->get('/courses?q=Advertising');
        $response->assertStatus(200);
        $response->assertSee('TikTok Organic Brand Playbook');
        $response->assertDontSee('Closing High Ticket Retainers');
    }

    /**
     * TEST 5: Search trims whitespace and handles case insensitivity.
     */
    public function test_search_trims_whitespace_and_handles_case_insensitivity(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Search Engine Marketing',
            'slug' => 'search-engine-marketing',
            'short_description' => 'SEM and Google Ads for startups.',
            'price' => 1299.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $response = $this->get('/courses?q=' . urlencode('   sEaRcH eNgInE   '));
        $response->assertStatus(200);
        $response->assertSee('Search Engine Marketing');
    }

    /**
     * TEST 6: Search relevance ordering ranks exact title match above description match.
     */
    public function test_search_relevance_ordering_ranks_title_match_above_description_match(): void
    {
        // Course A has the term only in its description
        $courseA = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'General Business Strategy',
            'slug' => 'general-business-strategy',
            'short_description' => 'A complete overview that covers Facebook Ads in detail.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Course B has the exact title
        $courseB = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Facebook Ads',
            'slug' => 'facebook-ads',
            'short_description' => 'Master paid acquisition on Meta.',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $response = $this->get('/courses?q=Facebook+Ads&sort=relevance');
        $response->assertStatus(200);

        // Course B should appear before Course A in HTML output
        $content = $response->getContent();
        $posB = strpos($content, 'Facebook Ads');
        $posA = strpos($content, 'General Business Strategy');

        $this->assertNotFalse($posB);
        $this->assertNotFalse($posA);
        $this->assertLessThan($posA, $posB, 'Exact title match should appear before description match under relevance sort.');
    }

    /**
     * TEST 7: Filter by category slug and invalid category handling.
     */
    public function test_filter_by_category_slug_and_handles_invalid_category_gracefully(): void
    {
        $cMarketing = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Marketing Foundations',
            'slug' => 'marketing-foundations',
            'short_description' => 'Core concepts of marketing.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $cSales = Course::create([
            'course_category_id' => $this->categorySales->id,
            'title' => 'Cold Calling Secrets',
            'slug' => 'cold-calling-secrets',
            'short_description' => 'Outbound telephone sales.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Valid category filter
        $res = $this->get('/courses?category=marketing-strategy');
        $res->assertStatus(200);
        $res->assertSee('Marketing Foundations');
        $res->assertDontSee('Cold Calling Secrets');

        // Invalid category falls back gracefully
        $resInvalid = $this->get('/courses?category=non-existent-category-slug');
        $resInvalid->assertStatus(200);
        $resInvalid->assertSee('Marketing Foundations');
        $resInvalid->assertSee('Cold Calling Secrets');
    }

    /**
     * TEST 8: Filter by course type (free vs paid).
     */
    public function test_filter_by_course_type_free_and_paid(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Free Marketing Starter Kit',
            'slug' => 'free-starter-kit',
            'short_description' => 'Get started with zero cost.',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Premium Accelerator Program',
            'slug' => 'premium-accelerator',
            'short_description' => 'High impact intensive coaching.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Free filter
        $resFree = $this->get('/courses?type=free');
        $resFree->assertStatus(200);
        $resFree->assertSee('Free Marketing Starter Kit');
        $resFree->assertDontSee('Premium Accelerator Program');

        // Paid filter
        $resPaid = $this->get('/courses?type=paid');
        $resPaid->assertStatus(200);
        $resPaid->assertSee('Premium Accelerator Program');
        $resPaid->assertDontSee('Free Marketing Starter Kit');
    }

    /**
     * TEST 9: Filter by price range using effective price.
     */
    public function test_filter_by_price_range_using_effective_price(): void
    {
        // Budget course: ₹500
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Budget Marketing Guide',
            'slug' => 'budget-guide',
            'short_description' => 'Low cost marketing.',
            'price' => 500.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Mid-range course: ₹1500
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Mid Tier Growth Masterclass',
            'slug' => 'mid-tier-masterclass',
            'short_description' => 'Middle budget program.',
            'price' => 1500.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Premium course: ₹5000
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Executive Strategic Immersion',
            'slug' => 'executive-immersion',
            'short_description' => 'High price program.',
            'price' => 5000.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Range ₹1000 - ₹2000
        $res = $this->get('/courses?min_price=1000&max_price=2000');
        $res->assertStatus(200);
        $res->assertSee('Mid Tier Growth Masterclass');
        $res->assertDontSee('Budget Marketing Guide');
        $res->assertDontSee('Executive Strategic Immersion');
    }

    /**
     * TEST 10: Discounted course uses discount_price for price filtering.
     */
    public function test_discounted_course_uses_discount_price_for_price_filtering(): void
    {
        // Original price is ₹4999, but discounted to ₹999
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Discounted Flash Sale Course',
            'slug' => 'flash-sale-course',
            'short_description' => 'Heavy discount available.',
            'price' => 4999.00,
            'discount_price' => 999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // With max_price = 1500, it SHOULD be included because effective price is 999
        $res = $this->get('/courses?max_price=1500');
        $res->assertStatus(200);
        $res->assertSee('Discounted Flash Sale Course');

        // With min_price = 2000, it should NOT be included
        $res2 = $this->get('/courses?min_price=2000');
        $res2->assertStatus(200);
        $res2->assertDontSee('Discounted Flash Sale Course');
    }

    /**
     * TEST 11: Free course is evaluated as 0.00 in price filtering.
     */
    public function test_free_course_evaluated_as_zero_in_price_filtering(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Zero Cost Free Course',
            'slug' => 'zero-cost-free',
            'short_description' => 'Completely free course.',
            'price' => 999.00, // even if original price had a dummy number
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Filter min_price = 100 should exclude the free course
        $res = $this->get('/courses?min_price=100');
        $res->assertStatus(200);
        $res->assertDontSee('Zero Cost Free Course');

        // Filter max_price = 50 should include the free course
        $res2 = $this->get('/courses?max_price=50');
        $res2->assertStatus(200);
        $res2->assertSee('Zero Cost Free Course');
    }

    /**
     * TEST 12: Filter by minimum average rating.
     */
    public function test_filter_by_minimum_average_rating(): void
    {
        $highRated = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Five Star Masterclass',
            'slug' => 'five-star-masterclass',
            'short_description' => 'Acclaimed course.',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $lowRated = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Two Star Basic Course',
            'slug' => 'two-star-basic',
            'short_description' => 'Needs improvement.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $unrated = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Brand New Unrated Course',
            'slug' => 'brand-new-unrated',
            'short_description' => 'No reviews yet.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Add 5-star review to highRated
        CourseReview::create([
            'course_id' => $highRated->id,
            'user_id' => $this->student->id,
            'rating' => 5,
            'review' => 'Excellent course material!',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        // Add 2-star review to lowRated
        CourseReview::create([
            'course_id' => $lowRated->id,
            'user_id' => $this->student->id,
            'rating' => 2,
            'review' => 'A bit shallow.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        // Filter rating >= 4.0
        $res = $this->get('/courses?rating=4.0');
        $res->assertStatus(200);
        $res->assertSee('Five Star Masterclass');
        $res->assertDontSee('Two Star Basic Course');
        $res->assertDontSee('Brand New Unrated Course');
    }

    /**
     * TEST 13: Filter by duration (short <3h, medium 3-10h, long >10h).
     */
    public function test_filter_by_duration_short_medium_and_long(): void
    {
        $shortCourse = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Quick 90 Min Sprint',
            'slug' => 'quick-sprint',
            'short_description' => 'Fast condensed course.',
            'estimated_duration' => '1.5 Hours',
            'price' => 499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $mediumCourse = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Solid 6 Hour Foundation',
            'slug' => 'six-hour-foundation',
            'short_description' => 'Standard length course.',
            'estimated_duration' => '6 Hours',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $longCourse = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Deep 20 Hour Bootcamp',
            'slug' => 'deep-bootcamp',
            'short_description' => 'In-depth comprehensive bootcamp.',
            'estimated_duration' => '20 Hours',
            'price' => 2999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Test short (< 3h)
        $resShort = $this->get('/courses?duration=short');
        $resShort->assertStatus(200);
        $resShort->assertSee('Quick 90 Min Sprint');
        $resShort->assertDontSee('Solid 6 Hour Foundation');
        $resShort->assertDontSee('Deep 20 Hour Bootcamp');

        // Test medium (3 - 10h)
        $resMed = $this->get('/courses?duration=medium');
        $resMed->assertStatus(200);
        $resMed->assertSee('Solid 6 Hour Foundation');
        $resMed->assertDontSee('Quick 90 Min Sprint');
        $resMed->assertDontSee('Deep 20 Hour Bootcamp');

        // Test long (> 10h)
        $resLong = $this->get('/courses?duration=long');
        $resLong->assertStatus(200);
        $resLong->assertSee('Deep 20 Hour Bootcamp');
        $resLong->assertDontSee('Quick 90 Min Sprint');
        $resLong->assertDontSee('Solid 6 Hour Foundation');
    }

    /**
     * TEST 14: Filter by featured status.
     */
    public function test_filter_by_featured_courses_only(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Featured Highlight Course',
            'slug' => 'featured-highlight',
            'short_description' => 'Flagship offering.',
            'price' => 1999.00,
            'featured' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Standard Regular Course',
            'slug' => 'standard-regular',
            'short_description' => 'Regular course.',
            'price' => 1999.00,
            'featured' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $res = $this->get('/courses?featured=1');
        $res->assertStatus(200);
        $res->assertSee('Featured Highlight Course');
        $res->assertDontSee('Standard Regular Course');
    }

    /**
     * TEST 15: Combining multiple filters together accurately.
     */
    public function test_combining_multiple_filters_together(): void
    {
        // Target match: Marketing + Free + Duration short (< 3h)
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Quick Free Marketing Primer',
            'slug' => 'free-primer',
            'short_description' => 'Fast free starter.',
            'estimated_duration' => '2 Hours',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Miss 1: Sales + Free + Duration short
        Course::create([
            'course_category_id' => $this->categorySales->id,
            'title' => 'Quick Free Sales Primer',
            'slug' => 'free-sales-primer',
            'short_description' => 'Sales starter.',
            'estimated_duration' => '2 Hours',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Miss 2: Marketing + Paid + Duration short
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Quick Paid Marketing Guide',
            'slug' => 'paid-primer',
            'short_description' => 'Paid starter.',
            'estimated_duration' => '2 Hours',
            'price' => 999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $res = $this->get('/courses?category=marketing-strategy&type=free&duration=short');
        $res->assertStatus(200);
        $res->assertSee('Quick Free Marketing Primer');
        $res->assertDontSee('Quick Free Sales Primer');
        $res->assertDontSee('Quick Paid Marketing Guide');
    }

    /**
     * TEST 16: Sorting options (price_low, price_high, rating, popular, oldest, newest).
     */
    public function test_sorting_options_work_as_expected(): void
    {
        $cheap = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Cheap Low Price Course',
            'slug' => 'cheap-course',
            'short_description' => 'Low price.',
            'price' => 299.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $expensive = Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Expensive High Price Course',
            'slug' => 'expensive-course',
            'short_description' => 'High price.',
            'price' => 9999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Sort price_low: cheap appears first
        $resLow = $this->get('/courses?sort=price_low');
        $resLow->assertStatus(200);
        $contentLow = $resLow->getContent();
        $this->assertLessThan(
            strpos($contentLow, 'Expensive High Price Course'),
            strpos($contentLow, 'Cheap Low Price Course')
        );

        // Sort price_high: expensive appears first
        $resHigh = $this->get('/courses?sort=price_high');
        $resHigh->assertStatus(200);
        $contentHigh = $resHigh->getContent();
        $this->assertLessThan(
            strpos($contentHigh, 'Cheap Low Price Course'),
            strpos($contentHigh, 'Expensive High Price Course')
        );

        // Sort popular: course with more enrollments appears first
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $cheap->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $resPopular = $this->get('/courses?sort=popular');
        $resPopular->assertStatus(200);
        $contentPop = $resPopular->getContent();
        $this->assertLessThan(
            strpos($contentPop, 'Expensive High Price Course'),
            strpos($contentPop, 'Cheap Low Price Course')
        );
    }

    /**
     * TEST 17: Whitelisted sorting rejects SQL injection attempts and unknown values.
     */
    public function test_whitelisted_sorting_rejects_sql_injection_and_unknown_sort_parameters(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'SQL Safe Course',
            'slug' => 'sql-safe-course',
            'short_description' => 'Security validated.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Attempting injection in sort
        $res = $this->get('/courses?sort=id+ASC;--DROP+TABLE+users;');
        $res->assertStatus(200);
        $res->assertSee('SQL Safe Course');

        // Unrecognized sort falls back cleanly
        $res2 = $this->get('/courses?sort=random_nonexistent');
        $res2->assertStatus(200);
        $res2->assertSee('SQL Safe Course');
    }

    /**
     * TEST 18: Draft and archived courses are strictly excluded from search and filters.
     */
    public function test_draft_and_archived_courses_are_strictly_excluded_from_search(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Secret In-Progress Marketing Blueprint',
            'slug' => 'secret-blueprint',
            'short_description' => 'Draft only.',
            'price' => 999.00,
            'status' => CourseStatus::DRAFT,
        ]);

        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Outdated Archived Marketing Playbook',
            'slug' => 'outdated-playbook',
            'short_description' => 'Archived only.',
            'price' => 999.00,
            'status' => CourseStatus::ARCHIVED,
        ]);

        $res = $this->get('/courses?q=Marketing');
        $res->assertStatus(200);
        $res->assertDontSee('Secret In-Progress Marketing Blueprint');
        $res->assertDontSee('Outdated Archived Marketing Playbook');
    }

    /**
     * TEST 19: Pagination preserves all query filter parameters.
     */
    public function test_pagination_preserves_all_query_filter_parameters(): void
    {
        // Create 11 published courses
        for ($i = 1; $i <= 11; $i++) {
            Course::create([
                'course_category_id' => $this->categoryMarketing->id,
                'title' => sprintf('Batch Marketing Course %02d', $i),
                'slug' => sprintf('batch-marketing-%02d', $i),
                'short_description' => 'Pagination test item.',
                'price' => 1000.00,
                'status' => CourseStatus::PUBLISHED,
            ]);
        }

        $res = $this->get('/courses?category=marketing-strategy&min_price=500&sort=price_low');
        $res->assertStatus(200);

        // Assert query parameters persist in generated links
        $res->assertSee('category=marketing-strategy');
        $res->assertSee('min_price=500');
        $res->assertSee('sort=price_low');
    }

    /**
     * TEST 20: Active filter pills and individual clear links render in view.
     */
    public function test_active_filter_pills_and_clear_links_render_in_view(): void
    {
        Course::create([
            'course_category_id' => $this->categoryMarketing->id,
            'title' => 'Pill Test Course',
            'slug' => 'pill-test-course',
            'short_description' => 'Active filter pill test.',
            'price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $res = $this->get('/courses?q=Pill&category=marketing-strategy&type=paid');
        $res->assertStatus(200);

        // Assert active filters header and badges
        $res->assertSee('Active filters:');
        $res->assertSee('Keyword: "Pill"', false);
        $res->assertSee('Category: Marketing Strategy');
        $res->assertSee('Type: Paid');
        $res->assertSee('Clear all');
    }

    /**
     * TEST 21: Empty search results renders helpful empty state with reset link.
     */
    public function test_empty_search_results_renders_helpful_empty_state_with_reset_link(): void
    {
        $res = $this->get('/courses?q=NonExistentGibberishTerm123');
        $res->assertStatus(200);
        $res->assertSee('No courses found matching your criteria');
        $res->assertSee('Clear Filters &amp; View All', false);
        $res->assertSee(route('courses'));
    }
}
