<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminInstructorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);
    }

    public function test_guest_cannot_access_instructors(): void
    {
        $response = $this->get(route('admin.instructors.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_instructors(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.instructors.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_instructors_index(): void
    {
        $instructor = Instructor::create([
            'name' => 'Chetan Gharate',
            'slug' => 'chetan-gharate',
            'title' => 'Senior Growth Marketer',
            'bio' => '10+ years of digital marketing and conversion optimization experience.',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.instructors.index'));

        $response->assertOk();
        $response->assertSee('Instructor Management');
        $response->assertSee('Chetan Gharate');
        $response->assertSee('Senior Growth Marketer');
    }

    public function test_admin_can_view_create_instructor_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.instructors.create'));

        $response->assertOk();
        $response->assertSee('Add New Instructor');
        $response->assertSee('Full Name');
    }

    public function test_admin_can_store_instructor_with_auto_generated_slug(): void
    {
        $data = [
            'name' => 'Priya Sharma',
            'title' => 'Performance Marketing Lead',
            'bio' => 'Specializes in paid media and ROI-positive ad campaigns.',
            'website_url' => 'https://example.com',
            'linkedin_url' => 'https://linkedin.com/in/priyasharma',
            'twitter_url' => 'https://x.com/priyasharma',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.instructors.store'), $data);

        $response->assertRedirect(route('admin.instructors.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('instructors', [
            'name' => 'Priya Sharma',
            'slug' => 'priya-sharma',
            'title' => 'Performance Marketing Lead',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'created',
            'auditable_type' => 'Instructor',
        ]);
    }

    public function test_admin_can_upload_avatar_image(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 400, 400);

        $data = [
            'name' => 'Arun Joshi',
            'slug' => 'arun-joshi',
            'title' => 'SEO Director',
            'avatar' => $file,
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.instructors.store'), $data);

        $response->assertRedirect(route('admin.instructors.index'));

        $instructor = Instructor::where('slug', 'arun-joshi')->first();
        $this->assertNotNull($instructor);
        $this->assertNotNull($instructor->avatar);

        Storage::disk('public')->assertExists($instructor->avatar);
    }

    public function test_admin_avatar_upload_fails_on_invalid_mime(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $data = [
            'name' => 'Invalid Avatar Guy',
            'avatar' => $file,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.instructors.store'), $data);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_admin_can_view_edit_instructor_form(): void
    {
        $instructor = Instructor::create([
            'name' => 'Sarah Connor',
            'slug' => 'sarah-connor',
            'title' => 'Brand Strategist',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.instructors.edit', $instructor));

        $response->assertOk();
        $response->assertSee('Edit: Sarah Connor');
        $response->assertSee('Brand Strategist');
    }

    public function test_admin_can_update_instructor(): void
    {
        $instructor = Instructor::create([
            'name' => 'Sarah Connor',
            'slug' => 'sarah-connor',
            'title' => 'Brand Strategist',
            'is_active' => true,
        ]);

        $data = [
            'name' => 'Sarah Connor-Smith',
            'slug' => 'sarah-connor-smith',
            'title' => 'Chief Brand Officer',
            'bio' => 'Updated biography description.',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.instructors.update', $instructor), $data);

        $response->assertRedirect(route('admin.instructors.index'));
        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'name' => 'Sarah Connor-Smith',
            'slug' => 'sarah-connor-smith',
            'title' => 'Chief Brand Officer',
        ]);
    }

    public function test_admin_can_toggle_instructor_active_status(): void
    {
        $instructor = Instructor::create([
            'name' => 'Toggle Test',
            'slug' => 'toggle-test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->patch(route('admin.instructors.toggle', $instructor));

        $response->assertRedirect();
        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'is_active' => false,
        ]);

        $response2 = $this->actingAs($this->admin)->patch(route('admin.instructors.toggle', $instructor));
        $response2->assertRedirect();
        $this->assertDatabaseHas('instructors', [
            'id' => $instructor->id,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_delete_instructor_and_course_instructor_id_becomes_null(): void
    {
        $instructor = Instructor::create([
            'name' => 'Delete Me',
            'slug' => 'delete-me',
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Marketing for Entrepreneurs',
            'slug' => 'marketing-entrepreneurs-' . uniqid(),
            'short_description' => 'Test course description',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
            'instructor_id' => $instructor->id,
            'instructor_name' => $instructor->name,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.instructors.destroy', $instructor));

        $response->assertRedirect(route('admin.instructors.index'));
        $this->assertDatabaseMissing('instructors', ['id' => $instructor->id]);

        $course->refresh();
        $this->assertNull($course->instructor_id);
        $this->assertEquals('Delete Me', $course->instructor_name);
    }

    public function test_admin_can_assign_instructor_to_course_on_create(): void
    {
        $instructor = Instructor::create([
            'name' => 'Assigned Instructor',
            'slug' => 'assigned-instructor',
            'title' => 'Course Master',
            'is_active' => true,
        ]);

        $courseData = [
            'title' => 'Growth Hacking Masterclass',
            'slug' => 'growth-hacking-masterclass',
            'short_description' => 'Advanced tactics for fast startup growth.',
            'instructor_id' => $instructor->id,
            'is_free' => '0',
            'price' => '2499.00',
            'status' => CourseStatus::PUBLISHED->value,
            'featured' => '0',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), $courseData);

        $response->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'slug' => 'growth-hacking-masterclass',
            'instructor_id' => $instructor->id,
            'instructor_name' => 'Assigned Instructor',
        ]);
    }

    public function test_admin_can_assign_instructor_to_course_on_update(): void
    {
        $instructor = Instructor::create([
            'name' => 'Updated Instructor',
            'slug' => 'updated-instructor',
            'title' => 'Funnel Architect',
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'Funnel Building 101',
            'slug' => 'funnel-building-101',
            'short_description' => 'How to construct high converting sales funnels.',
            'price' => 1499.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->assertNull($course->instructor_id);

        $updateData = [
            'title' => 'Funnel Building 101 Updated',
            'slug' => 'funnel-building-101',
            'short_description' => 'How to construct high converting sales funnels.',
            'instructor_id' => $instructor->id,
            'is_free' => '0',
            'price' => '1499.00',
            'status' => CourseStatus::PUBLISHED->value,
            'featured' => '0',
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.courses.update', $course), $updateData);

        $response->assertRedirect(route('admin.courses.index'));

        $course->refresh();
        $this->assertEquals($instructor->id, $course->instructor_id);
        $this->assertEquals('Updated Instructor', $course->instructor_name);
    }

    public function test_public_course_show_displays_instructor_profile(): void
    {
        $instructor = Instructor::create([
            'name' => 'Vikram Malhotra',
            'slug' => 'vikram-malhotra',
            'title' => 'E-Commerce Growth Specialist',
            'bio' => 'Generated over 10M in direct-to-consumer sales.',
            'website_url' => 'https://malhotramarketing.com',
            'linkedin_url' => 'https://linkedin.com/in/vmalhotra',
            'is_active' => true,
        ]);

        $course = Course::create([
            'title' => 'E-Commerce Mastery',
            'slug' => 'ecommerce-mastery',
            'short_description' => 'Scale your shopify store with precision.',
            'price' => 3999.00,
            'status' => CourseStatus::PUBLISHED,
            'instructor_id' => $instructor->id,
        ]);

        $response = $this->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Meet Your Instructor');
        $response->assertSee('Vikram Malhotra');
        $response->assertSee('E-Commerce Growth Specialist');
        $response->assertSee('Generated over 10M in direct-to-consumer sales.');
        $response->assertSee('https://malhotramarketing.com');
        $response->assertSee('https://linkedin.com/in/vmalhotra');
    }

    public function test_public_course_show_falls_back_when_no_instructor(): void
    {
        $course = Course::create([
            'title' => 'General Marketing Foundations',
            'slug' => 'general-marketing-foundations',
            'short_description' => 'Learn the basics without fluff.',
            'price' => 999.00,
            'status' => CourseStatus::PUBLISHED,
            'instructor_name' => 'Marketian Mind Core Team',
        ]);

        $response = $this->get(route('courses.show', $course));

        $response->assertOk();
        $response->assertSee('Meet Your Instructor');
        $response->assertSee('Marketian Mind Core Team');
    }

    public function test_instructor_slug_uniqueness_handles_collisions(): void
    {
        $instructor1 = Instructor::create([
            'name' => 'Alex Rivera',
            'slug' => 'alex-rivera',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.instructors.store'), [
            'name' => 'Alex Rivera',
            'title' => 'SEO Expert',
        ]);

        $response->assertRedirect(route('admin.instructors.index'));

        $this->assertDatabaseHas('instructors', [
            'name' => 'Alex Rivera',
            'slug' => 'alex-rivera-1',
        ]);
    }
}