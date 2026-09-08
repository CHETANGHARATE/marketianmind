<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test that the home page returns a successful response and displays the brand.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Marketian Mind');
    }

    /**
     * Test that the student portal placeholder returns a successful response.
     */
    public function test_the_student_portal_returns_a_successful_response(): void
    {
        $response = $this->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Student Dashboard');
    }

    /**
     * Test that the admin portal placeholder returns a successful response.
     */
    public function test_the_admin_portal_returns_a_successful_response(): void
    {
        $response = $this->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }
}
