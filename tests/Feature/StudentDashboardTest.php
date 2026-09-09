<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Unauthenticated users cannot access Student Dashboard.
     */
    public function test_unauthenticated_guests_cannot_access_student_dashboard(): void
    {
        $this->get('/student/dashboard')->assertRedirect('/login');
        $this->get('/student/my-learning')->assertRedirect('/login');
        $this->get('/student/courses')->assertRedirect('/login');
        $this->get('/student/progress')->assertRedirect('/login');
        $this->get('/student/profile')->assertRedirect('/login');
    }

    /**
     * TEST 2: Student users can access Student Dashboard.
     */
    public function test_student_users_can_access_student_dashboard(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Student Dashboard');
        $response->assertSee('Learning Overview');
        $response->assertSee('Enrolled Courses');
        $response->assertSee('Lessons Completed');
    }

    /**
     * TEST 3: Admin users cannot access Student-only pages.
     */
    public function test_admin_users_cannot_access_student_only_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/student/dashboard')->assertStatus(403);
        $this->actingAs($admin)->get('/student/my-learning')->assertStatus(403);
        $this->actingAs($admin)->get('/student/courses')->assertStatus(403);
        $this->actingAs($admin)->get('/student/progress')->assertStatus(403);
        $this->actingAs($admin)->get('/student/profile')->assertStatus(403);
    }

    /**
     * TEST 4: Student Dashboard displays the authenticated user's name.
     */
    public function test_student_dashboard_displays_authenticated_user_name(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Sophia Founder',
        ]);

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Welcome back, Sophia Founder!');
    }

    /**
     * TEST 5: My Learning page loads with empty state.
     */
    public function test_my_learning_page_loads_with_professional_empty_state(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/student/my-learning');

        $response->assertStatus(200);
        $response->assertSee('My Learning');
        $response->assertSee('started any courses yet');
        $response->assertSee('Browse Courses');
    }

    /**
     * TEST 6: Progress page loads with zero-state information.
     */
    public function test_progress_page_loads_with_zero_state_information(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/student/progress');

        $response->assertStatus(200);
        $response->assertSee('Learning Progress');
        $response->assertSee('Learning Hours');
        $response->assertSee('Completed Lessons');
        $response->assertSee('Your learning journey will appear here once you start a course');
    }

    /**
     * TEST 7: Browse Courses page loads and lists courses.
     */
    public function test_browse_courses_page_loads_with_available_curricula(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/student/courses');

        $response->assertStatus(200);
        $response->assertSee('Browse Courses');
        $response->assertSee('Digital Marketing for Business Owners');
        $response->assertSee('Course Curriculum', false);
    }

    /**
     * TEST 8: Profile page loads with user details.
     */
    public function test_profile_page_loads_with_authenticated_student_details(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Michael Merchant',
            'email' => 'michael@startup.com',
        ]);

        $response = $this->actingAs($student)->get('/student/profile');

        $response->assertStatus(200);
        $response->assertSee('Student Profile');
        $response->assertSee('Michael Merchant');
        $response->assertSee('michael@startup.com');
        $response->assertSee('Student');
        $response->assertSee('Active Account');
    }

    /**
     * TEST 9: Navigation links are present in the student layout.
     */
    public function test_student_layout_contains_sidebar_navigation_links(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/student/dashboard');

        $response->assertStatus(200);
        $response->assertSee(route('student.dashboard'));
        $response->assertSee(route('student.my-learning'));
        $response->assertSee(route('student.courses'));
        $response->assertSee(route('student.progress'));
        $response->assertSee(route('student.profile'));
        $response->assertSee(route('logout'));
    }

    /**
     * TEST 10: Logout works correctly from authenticated state.
     */
    public function test_student_can_logout_and_is_redirected_to_homepage(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * TEST 11: Existing Login and Registration functionality still works.
     */
    public function test_login_and_registration_pages_remain_accessible_for_guests(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
    }

    /**
     * TEST 12: Existing Admin Dashboard functionality is not broken.
     */
    public function test_admin_dashboard_remains_functional_for_admins(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Admin Dashboard');
    }

    /**
     * TEST 13: Existing Public Homepage functionality is not broken.
     */
    public function test_public_homepage_remains_fully_functional(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Marketing Knowledge for Business Owners');
    }
}