<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Registration page loads successfully.
     */
    public function test_registration_page_loads_successfully(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Create Your Account');
        $response->assertSee('Full Name');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
        $response->assertSee('Confirm Password');
        $response->assertSee('Marketian');
    }

    /**
     * TEST 2, 3, 4: New user registration works, auto-assigns student role, and redirects to student dashboard.
     */
    public function test_new_user_registration_works_assigns_student_role_and_redirects(): void
    {
        $response = $this->post('/register', [
            'name' => 'Alice Founder',
            'email' => 'alice@business.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'admin', // Attempted role manipulation should be ignored
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'alice@business.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Alice Founder', $user->name);
        $this->assertEquals(UserRole::STUDENT, $user->role);
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isAdmin());

        $response->assertRedirect('/student/dashboard');
    }

    /**
     * Registration fails with invalid data.
     */
    public function test_registration_requires_matching_password_and_unique_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        // Test non-matching password
        $responseMismatch = $this->post('/register', [
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'password123',
            'password_confirmation' => 'mismatch123',
        ]);
        $responseMismatch->assertSessionHasErrors(['password']);

        // Test duplicate email
        $responseDup = $this->post('/register', [
            'name' => 'Existing',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $responseDup->assertSessionHasErrors(['email']);
    }

    /**
     * Login screen can be rendered.
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Welcome Back');
        $response->assertSee('Email Address');
        $response->assertSee('Password');
        $response->assertSee('Remember me');
        $response->assertSee('Sign In');
    }

    /**
     * TEST 5: Student login works and redirects to student dashboard.
     */
    public function test_student_can_authenticate_and_is_redirected_to_student_dashboard(): void
    {
        $student = User::factory()->student()->create([
            'email' => 'student@marketianmind.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'student@marketianmind.com',
            'password' => 'password123',
        ]);

        $this->assertAuthenticatedAs($student);
        $response->assertRedirect('/student/dashboard');
    }

    /**
     * TEST 6: Admin login works and redirects to admin dashboard.
     */
    public function test_admin_can_authenticate_and_is_redirected_to_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@marketianmind.com',
            'password' => Hash::make('adminpass123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@marketianmind.com',
            'password' => 'adminpass123',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * Invalid credentials show generic error without revealing email existence.
     */
    public function test_users_cannot_authenticate_with_invalid_password_or_unregistered_email(): void
    {
        User::factory()->create([
            'email' => 'valid@example.com',
            'password' => Hash::make('correct-password'),
        ]);

        // Wrong password for existing user
        $responseWrongPass = $this->post('/login', [
            'email' => 'valid@example.com',
            'password' => 'wrong-password',
        ]);
        $responseWrongPass->assertSessionHasErrors('email');
        $this->assertGuest();

        // Non-existent email
        $responseNoUser = $this->post('/login', [
            'email' => 'doesnotexist@example.com',
            'password' => 'randompass',
        ]);
        $responseNoUser->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * Remember me functionality works.
     */
    public function test_user_can_authenticate_with_remember_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => 'on',
        ]);

        $this->assertAuthenticated();
        $response->assertCookieNotExpired(Auth::getRecallerName());
    }

    /**
     * TEST 10: Logout works correctly and invalidates session.
     */
    public function test_users_can_logout_and_are_redirected_to_homepage(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    /**
     * TEST 11: Authenticated users visiting login/register are redirected based on role.
     */
    public function test_authenticated_student_is_redirected_away_from_guest_routes(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/login')->assertRedirect('/student/dashboard');
        $this->actingAs($student)->get('/register')->assertRedirect('/student/dashboard');
    }

    public function test_authenticated_admin_is_redirected_away_from_guest_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/login')->assertRedirect('/admin/dashboard');
        $this->actingAs($admin)->get('/register')->assertRedirect('/admin/dashboard');
    }

    /**
     * Navbar renders correct links for guests, students, and admins.
     */
    public function test_navbar_renders_appropriate_links_for_different_roles(): void
    {
        // Guest view
        $guestResponse = $this->get('/');
        $guestResponse->assertSee('Login');
        $guestResponse->assertSee('Create Account');
        $guestResponse->assertDontSee('Logout');

        // Student view
        $student = User::factory()->student()->create(['name' => 'Sam Student']);
        $studentResponse = $this->actingAs($student)->get('/');
        $studentResponse->assertSee('My Learning / Dashboard');
        $studentResponse->assertSee('Sam Student');
        $studentResponse->assertSee('Logout');

        // Admin view
        $admin = User::factory()->admin()->create(['name' => 'Alex Admin']);
        $adminResponse = $this->actingAs($admin)->get('/');
        $adminResponse->assertSee('Admin Dashboard');
        $adminResponse->assertSee('Logout');
    }
}