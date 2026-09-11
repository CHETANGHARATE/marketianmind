<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingConversionTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_displays_featured_courses_dynamically(): void
    {
        $category = CourseCategory::create([
            'name' => 'E-Commerce Marketing',
            'slug' => 'e-commerce-marketing',
            'is_active' => true,
        ]);

        $featuredCourse = Course::create([
            'title' => 'Shopify Conversion Mastery',
            'slug' => 'shopify-conversion-mastery',
            'short_description' => 'Turn website clicks into high value orders.',
            'course_category_id' => $category->id,
            'price' => 3499.00,
            'discount_price' => 2499.00,
            'featured' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Featured Marketing Courses');
        $response->assertSee('Shopify Conversion Mastery');
        $response->assertSee('E-Commerce Marketing');
        $response->assertSee('2,499.00');
    }

    public function test_home_page_displays_trust_guarantee_and_faq(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('30-Day Guarantee');
        $response->assertSee('Lifetime Access');
        $response->assertSee('Verified Certificate');
        $response->assertSee('Frequently Asked Questions');
        $response->assertSee('Do I need prior marketing or technical experience?');
        $response->assertSee('Not sure which course is right for your business?');
    }

    public function test_public_course_show_displays_trust_strip_and_faq(): void
    {
        $course = Course::create([
            'title' => 'Email Marketing for Founders',
            'slug' => 'email-marketing-founders',
            'short_description' => 'Build and monetize your customer email list.',
            'price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $response = $this->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('30-Day Money-Back Guarantee');
        $response->assertSee('Course Frequently Asked Questions');
        $response->assertSee('Have questions before enrolling?');
    }

    public function test_home_quick_inquiry_form_stores_lead(): void
    {
        $data = [
            'name' => 'Devika Pillai',
            'email' => 'devika@handmadestudio.com',
            'source' => 'home_quick_inquiry',
            'message' => 'I have a pottery studio and need to sell online.',
            'website' => '',
        ];

        $response = $this->post(route('leads.store'), $data);

        $response->assertRedirect();
        $response->assertSessionHas('lead_success');

        $this->assertDatabaseHas('leads', [
            'name' => 'Devika Pillai',
            'email' => 'devika@handmadestudio.com',
            'source' => 'home_quick_inquiry',
        ]);
    }
}