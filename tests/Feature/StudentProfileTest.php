<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TEST 1: Guest cannot access student profile.
     */
    public function test_guest_cannot_access_student_profile(): void
    {
        $this->get('/student/profile')->assertRedirect('/login');
        $this->put('/student/profile', ['name' => 'Name', 'email' => 'email@test.com'])->assertRedirect('/login');
        $this->put('/student/profile/password', ['current_password' => 'pass', 'password' => 'newpass'])->assertRedirect('/login');
    }

    /**
     * TEST 2: Student can access student profile.
     */
    public function test_student_can_access_student_profile(): void
    {
        $student = User::factory()->student()->create();

        $response = $this->actingAs($student)->get('/student/profile');

        $response->assertStatus(200);
        $response->assertSee('Student Profile');
        $response->assertSee('Personal Information');
        $response->assertSee('Security & Password', false);
        $response->assertSee('Account Information');
    }

    /**
     * TEST 3: Admin cannot access Student Profile.
     */
    public function test_admin_cannot_access_student_profile(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/student/profile')->assertStatus(403);
        $this->actingAs($admin)->put('/student/profile', ['name' => 'Admin Name', 'email' => 'admin@test.com'])->assertStatus(403);
        $this->actingAs($admin)->put('/student/profile/password', [])->assertStatus(403);
    }

    /**
     * TEST 4: Authenticated student name and email display correctly.
     */
    public function test_authenticated_student_name_and_email_display_correctly(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Victoria Merchant',
            'email' => 'victoria@growthbrand.com',
        ]);

        $response = $this->actingAs($student)->get('/student/profile');

        $response->assertStatus(200);
        $response->assertSee('Victoria Merchant');
        $response->assertSee('victoria@growthbrand.com');
        $response->assertSee('Student');
        $response->assertSee('Active Account');
    }

    /**
     * TEST 5: Student can update their own name.
     */
    public function test_student_can_update_their_own_name(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Original Name',
            'email' => 'student@marketian.com',
        ]);

        $response = $this->actingAs($student)->put('/student/profile', [
            'name' => 'Updated Business Owner',
            'email' => 'student@marketian.com',
        ]);

        $response->assertSessionHas('profile_status');
        $response->assertRedirect();

        $student->refresh();
        $this->assertEquals('Updated Business Owner', $student->name);
        $this->assertEquals('student@marketian.com', $student->email);
    }

    /**
     * TEST 6: Student can update their own email.
     */
    public function test_student_can_update_their_own_email(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Student Name',
            'email' => 'old@marketian.com',
        ]);

        $response = $this->actingAs($student)->put('/student/profile', [
            'name' => 'Student Name',
            'email' => 'new@marketian.com',
        ]);

        $response->assertSessionHas('profile_status');
        $student->refresh();
        $this->assertEquals('new@marketian.com', $student->email);
    }

    /**
     * TEST 7: Duplicate email is rejected.
     */
    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'another@marketian.com']);
        $student = User::factory()->student()->create(['email' => 'current@marketian.com']);

        $response = $this->actingAs($student)->put('/student/profile', [
            'name' => 'Student Name',
            'email' => 'another@marketian.com',
        ]);

        $response->assertSessionHasErrors(['email']);
        $student->refresh();
        $this->assertEquals('current@marketian.com', $student->email);
    }

    /**
     * TEST 8: Invalid profile data is rejected (empty fields, invalid email format, name too long).
     */
    public function test_invalid_profile_data_is_rejected(): void
    {
        $student = User::factory()->student()->create([
            'name' => 'Valid Name',
            'email' => 'valid@marketian.com',
        ]);

        // Empty name
        $res1 = $this->actingAs($student)->put('/student/profile', [
            'name' => '',
            'email' => 'valid@marketian.com',
        ]);
        $res1->assertSessionHasErrors(['name']);

        // Empty email
        $res2 = $this->actingAs($student)->put('/student/profile', [
            'name' => 'Valid Name',
            'email' => '',
        ]);
        $res2->assertSessionHasErrors(['email']);

        // Invalid email format
        $res3 = $this->actingAs($student)->put('/student/profile', [
            'name' => 'Valid Name',
            'email' => 'not-an-email-address',
        ]);
        $res3->assertSessionHasErrors(['email']);

        // Name too long (> 255 chars)
        $res4 = $this->actingAs($student)->put('/student/profile', [
            'name' => str_repeat('a', 256),
            'email' => 'valid@marketian.com',
        ]);
        $res4->assertSessionHasErrors(['name']);

        // Ensure database state was unchanged
        $student->refresh();
        $this->assertEquals('Valid Name', $student->name);
        $this->assertEquals('valid@marketian.com', $student->email);
    }

    /**
     * TEST 9: Wrong current password prevents password change.
     */
    public function test_wrong_current_password_prevents_password_change(): void
    {
        $student = User::factory()->student()->create([
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->actingAs($student)->put('/student/profile/password', [
            'current_password' => 'WrongPassword123!',
            'password' => 'NewValidPassword123!',
            'password_confirmation' => 'NewValidPassword123!',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $student->refresh();
        $this->assertTrue(Hash::check('CorrectPassword123!', $student->password));
    }

    /**
     * TEST 10: Valid password change succeeds.
     */
    public function test_valid_password_change_succeeds(): void
    {
        $student = User::factory()->student()->create([
            'email' => 'securestudent@marketian.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->actingAs($student)->put('/student/profile/password', [
            'current_password' => 'OldPassword123!',
            'password' => 'BrandNewPassword123!',
            'password_confirmation' => 'BrandNewPassword123!',
        ]);

        $response->assertSessionHas('password_status');

        $student->refresh();
        $this->assertTrue(Hash::check('BrandNewPassword123!', $student->password));
        $this->assertFalse(Hash::check('OldPassword123!', $student->password));

        // Logout and verify new credentials work on login
        $this->post('/logout');

        // Test login with old password fails
        $failResponse = $this->post('/login', [
            'email' => 'securestudent@marketian.com',
            'password' => 'OldPassword123!',
        ]);
        $failResponse->assertSessionHasErrors('email');
        $this->assertGuest();

        // Test login with new password succeeds
        $successResponse = $this->post('/login', [
            'email' => 'securestudent@marketian.com',
            'password' => 'BrandNewPassword123!',
        ]);
        $successResponse->assertRedirect('/student/dashboard');
        $this->assertAuthenticatedAs($student);
    }

    /**
     * TEST 11: Password confirmation mismatch is rejected.
     */
    public function test_password_confirmation_mismatch_is_rejected(): void
    {
        $student = User::factory()->student()->create([
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = $this->actingAs($student)->put('/student/profile/password', [
            'current_password' => 'CorrectPassword123!',
            'password' => 'NewValidPassword123!',
            'password_confirmation' => 'MismatchingPassword999!',
        ]);

        $response->assertSessionHasErrors(['password']);
        $student->refresh();
        $this->assertTrue(Hash::check('CorrectPassword123!', $student->password));
    }

    /**
     * TEST 12: Password must be different from current password.
     */
    public function test_password_cannot_be_same_as_current_password(): void
    {
        $student = User::factory()->student()->create([
            'password' => Hash::make('SamePassword123!'),
        ]);

        $response = $this->actingAs($student)->put('/student/profile/password', [
            'current_password' => 'SamePassword123!',
            'password' => 'SamePassword123!',
            'password_confirmation' => 'SamePassword123!',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    /**
     * TEST 13: Student cannot modify another user's account.
     */
    public function test_another_users_account_cannot_be_modified(): void
    {
        $otherUser = User::factory()->student()->create([
            'name' => 'Original Other Name',
            'email' => 'other@marketian.com',
        ]);

        $student = User::factory()->student()->create([
            'name' => 'Current Student',
            'email' => 'student@marketian.com',
        ]);

        // Attempting to pass user_id or id in request must have zero effect on other user
        $this->actingAs($student)->put('/student/profile', [
            'id' => $otherUser->id,
            'user_id' => $otherUser->id,
            'name' => 'Attacked Name',
            'email' => 'student@marketian.com',
        ]);

        $otherUser->refresh();
        $this->assertEquals('Original Other Name', $otherUser->name);
        $this->assertEquals('other@marketian.com', $otherUser->email);

        $student->refresh();
        $this->assertEquals('Attacked Name', $student->name);
    }

    /**
     * TEST 14: Student cannot change their role via profile update.
     */
    public function test_student_cannot_modify_their_role_via_profile_update(): void
    {
        $student = User::factory()->student()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->actingAs($student)->put('/student/profile', [
            'name' => 'Hacker Name',
            'email' => $student->email,
            'role' => 'admin',
        ]);

        $student->refresh();
        $this->assertEquals(UserRole::STUDENT, $student->role);
        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isAdmin());
    }

    /**
     * TEST 15: Existing Dashboard functionality still works.
     */
    public function test_existing_student_dashboard_remains_functional(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get('/student/dashboard')->assertStatus(200);
        $this->actingAs($student)->get('/student/my-learning')->assertStatus(200);
        $this->actingAs($student)->get('/student/courses')->assertStatus(200);
        $this->actingAs($student)->get('/student/progress')->assertStatus(200);
        $this->actingAs($student)->get('/student/orders')->assertStatus(200);
    }

    /**
     * TEST 16: Existing Login and Registration functionality still works.
     */
    public function test_existing_login_and_registration_pages_remain_functional(): void
    {
        $this->get('/login')->assertStatus(200);
        $this->get('/register')->assertStatus(200);
    }

    /**
     * TEST 17: Admin Dashboard remains unaffected.
     */
    public function test_admin_dashboard_remains_functional(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertStatus(200);
    }

    /**
     * TEST 18: Public website remains unaffected.
     */
    public function test_public_website_remains_fully_functional(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/courses')->assertStatus(200);
        $this->get('/about')->assertStatus(200);
        $this->get('/contact')->assertStatus(200);
    }
}