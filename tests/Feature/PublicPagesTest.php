<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    /**
     * Test Home page loads and displays core brand messages.
     */
    public function test_home_page_is_successful_and_renders_brand_philosophy(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Marketing Knowledge for Business Owners');
        $response->assertSee('Learn how to grow your business online without depending on expensive marketing agencies');
        $response->assertSee('Explore Courses');
        $response->assertSee('Why Marketian Mind?');
    }

    /**
     * Test About page loads and displays philosophy and 5 pillars.
     */
    public function test_about_page_is_successful_and_renders_philosophy(): void
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('Marketing is not just posting on social media');
        $response->assertSee('Understanding Customers');
        $response->assertSee('Creating the Right Communication');
    }

    /**
     * Test Courses listing page loads with featured course.
     */
    public function test_courses_page_is_successful_and_lists_featured_course(): void
    {
        $response = $this->get('/courses');

        $response->assertStatus(200);
        $response->assertSee('Online Marketing Courses for Business Owners');
        $response->assertSee('Digital Marketing for Business Owners');
        $response->assertSee('Coming Soon');
    }

    /**
     * Test Course Details page loads with modules preview.
     */
    public function test_course_details_page_is_successful_and_shows_modules(): void
    {
        $response = $this->get('/courses/digital-marketing-for-business-owners');

        $response->assertStatus(200);
        $response->assertSee('Digital Marketing for Business Owners');
        $response->assertSee('Course Modules Preview');
        $response->assertSee('Understanding Digital Marketing');
        $response->assertSee('Social Media Marketing');
        $response->assertSee('Content Strategy');
    }

    /**
     * Test Contact page loads with form and contact info.
     */
    public function test_contact_page_is_successful_and_renders_form(): void
    {
        $response = $this->get('/contact');

        $response->assertStatus(200);
        $response->assertSee('Contact Marketian Mind');
        $response->assertSee('hello@marketianmind.com');
        $response->assertSee('Send Us a Message');
    }

    /**
     * Test Student and Admin placeholders are still operational.
     */
    public function test_portal_placeholders_remain_accessible(): void
    {
        $this->get('/student/dashboard')->assertStatus(200);
        $this->get('/admin/dashboard')->assertStatus(200);
    }
}
