<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register temporary test routes to verify role authorization middleware
        Route::middleware(['web', 'role:admin'])->get('/test/role-admin-only', function () {
            return response()->json(['status' => 'admin_access_granted']);
        });

        Route::middleware(['web', 'admin'])->get('/test/admin-middleware-only', function () {
            return response()->json(['status' => 'admin_granted']);
        });

        Route::middleware(['web', 'role:student'])->get('/test/role-student-only', function () {
            return response()->json(['status' => 'student_access_granted']);
        });

        Route::middleware(['web', 'student'])->get('/test/student-middleware-only', function () {
            return response()->json(['status' => 'student_granted']);
        });
    }

    /**
     * Test user creation defaults to student role.
     */
    public function test_user_defaults_to_student_role(): void
    {
        $user = User::factory()->create();

        $this->assertEquals(UserRole::STUDENT, $user->role);
        $this->assertTrue($user->isStudent());
        $this->assertFalse($user->isAdmin());
        $this->assertTrue($user->hasRole(UserRole::STUDENT));
        $this->assertTrue($user->hasRole('student'));
        $this->assertFalse($user->hasRole(UserRole::ADMIN));
        $this->assertFalse($user->hasRole('admin'));
    }

    /**
     * Test user can be created with admin role.
     */
    public function test_user_can_have_admin_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertEquals(UserRole::ADMIN, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStudent());
        $this->assertTrue($admin->hasRole(UserRole::ADMIN));
        $this->assertTrue($admin->hasRole('admin'));
    }

    /**
     * Test role is mass assignable and password is hidden.
     */
    public function test_role_is_mass_assignable_and_security_attributes_are_hidden(): void
    {
        $user = User::create([
            'name' => 'Jane Founder',
            'email' => 'jane@example.com',
            'password' => 'secret123',
            'role' => 'admin',
        ]);

        $this->assertEquals(UserRole::ADMIN, $user->role);
        $this->assertTrue($user->isAdmin());

        $userArray = $user->toArray();
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
        $this->assertArrayHasKey('role', $userArray);
    }

    /**
     * Test unauthenticated guests are denied access by role middleware.
     */
    public function test_guest_is_unauthorized_for_role_protected_routes(): void
    {
        $this->getJson('/test/role-admin-only')->assertStatus(401);
        $this->getJson('/test/admin-middleware-only')->assertStatus(401);
        $this->getJson('/test/role-student-only')->assertStatus(401);
        $this->getJson('/test/student-middleware-only')->assertStatus(401);
    }

    /**
     * Test student user cannot access admin-protected routes.
     */
    public function test_student_cannot_access_admin_routes(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->getJson('/test/role-admin-only')
            ->assertStatus(403);

        $this->actingAs($student)
            ->getJson('/test/admin-middleware-only')
            ->assertStatus(403);
    }

    /**
     * Test student user can access student-protected routes.
     */
    public function test_student_can_access_student_routes(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->getJson('/test/role-student-only')
            ->assertStatus(200)
            ->assertJson(['status' => 'student_access_granted']);

        $this->actingAs($student)
            ->getJson('/test/student-middleware-only')
            ->assertStatus(200)
            ->assertJson(['status' => 'student_granted']);
    }

    /**
     * Test admin user can access admin-protected routes.
     */
    public function test_admin_can_access_admin_routes(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/test/role-admin-only')
            ->assertStatus(200)
            ->assertJson(['status' => 'admin_access_granted']);

        $this->actingAs($admin)
            ->getJson('/test/admin-middleware-only')
            ->assertStatus(200)
            ->assertJson(['status' => 'admin_granted']);
    }

    /**
     * Test public pages load successfully and portal dashboards require appropriate authentication and role.
     */
    public function test_existing_public_pages_and_portal_dashboards_access_controls(): void
    {
        // Public pages remain accessible to all
        $this->get('/')->assertStatus(200);
        $this->get('/about')->assertStatus(200);
        $this->get('/courses')->assertStatus(200);
        $this->get('/courses/digital-marketing-for-business-owners')->assertStatus(200);
        $this->get('/contact')->assertStatus(200);

        // Guests are redirected to login for protected dashboards
        $this->get('/student/dashboard')->assertRedirect('/login');
        $this->get('/admin/dashboard')->assertRedirect('/login');

        // Student access control
        $student = User::factory()->student()->create();
        $this->actingAs($student)->get('/student/dashboard')->assertStatus(200);
        $this->actingAs($student)->get('/admin/dashboard')->assertStatus(403);

        // Admin access control (strict separation)
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);
        $this->actingAs($admin)->get('/student/dashboard')->assertStatus(403);
    }
}