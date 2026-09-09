<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

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
     * Test that the student portal returns a successful response for authenticated student.
     */
    public function test_the_student_portal_returns_a_successful_response(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Student Dashboard');
    }

    /**
     * Test that the admin portal returns a successful response for authenticated admin.
     */
    public function test_the_admin_portal_returns_a_successful_response(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }
}
