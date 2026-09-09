<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCourseManagementTest extends TestCase
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

    public function test_guests_are_redirected_from_admin_courses(): void
    {
        $response = $this->get(route('admin.courses.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_students_are_forbidden_from_admin_courses(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.courses.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_courses_index(): void
    {
        Course::create([
            'title' => 'Sample Marketing Course',
            'slug' => 'sample-marketing-course',
            'short_description' => 'A short description.',
            'status' => CourseStatus::PUBLISHED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.courses.index'));

        $response->assertStatus(200);
        $response->assertSee('Course Management');
        $response->assertSee('Sample Marketing Course');
    }

    public function test_admin_can_view_create_course_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.courses.create'));

        $response->assertStatus(200);
        $response->assertSee('Create New Course');
        $response->assertSee('Course Pricing');
    }

    public function test_admin_can_create_a_paid_course_with_thumbnail(): void
    {
        Storage::fake('public');

        $category = CourseCategory::create([
            'name' => 'SEO',
            'slug' => 'seo',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $thumbnail = UploadedFile::fake()->image('thumbnail.jpg', 600, 400);

        $payload = [
            'title' => 'Mastering Google Search',
            'slug' => 'mastering-google-search',
            'short_description' => 'Rank high on Google.',
            'description' => 'Comprehensive SEO training for founders.',
            'course_category_id' => $category->id,
            'instructor_name' => 'John SEO',
            'is_free' => '0',
            'price' => '2499.00',
            'discount_price' => '1499.00',
            'status' => 'published',
            'featured' => '1',
            'estimated_duration' => '5 hours',
            'thumbnail' => $thumbnail,
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('courses', [
            'title' => 'Mastering Google Search',
            'slug' => 'mastering-google-search',
            'course_category_id' => $category->id,
            'is_free' => 0,
            'featured' => 1,
            'status' => 'published',
        ]);

        $course = Course::where('slug', 'mastering-google-search')->first();
        $this->assertNotNull($course->thumbnail);
        Storage::disk('public')->assertExists($course->thumbnail);
    }

    public function test_admin_can_create_a_free_course(): void
    {
        $payload = [
            'title' => 'Free Marketing Starter Kit',
            'short_description' => 'Get started with zero budget.',
            'is_free' => '1',
            'price' => '0',
            'status' => 'published',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'title' => 'Free Marketing Starter Kit',
            'slug' => 'free-marketing-starter-kit',
            'is_free' => 1,
            'price' => 0.00,
            'status' => 'published',
        ]);
    }

    public function test_course_validation_enforces_rules(): void
    {
        // 1. Missing required title & short_description
        $response = $this->actingAs($this->admin)->post(route('admin.courses.store'), [
            'status' => 'draft',
        ]);
        $response->assertSessionHasErrors(['title', 'short_description']);

        // 2. Discount price cannot exceed regular price
        $response2 = $this->actingAs($this->admin)->post(route('admin.courses.store'), [
            'title' => 'Pricing Error Course',
            'short_description' => 'Test course.',
            'is_free' => '0',
            'price' => '1000.00',
            'discount_price' => '1500.00',
            'status' => 'draft',
        ]);
        $response2->assertSessionHasErrors(['discount_price']);
    }

    public function test_admin_can_update_course_and_replace_thumbnail(): void
    {
        Storage::fake('public');

        $oldThumbnail = UploadedFile::fake()->image('old.jpg');
        $oldPath = $oldThumbnail->store('courses/thumbnails', 'public');

        $course = Course::create([
            'title' => 'Old Title Course',
            'slug' => 'old-title-course',
            'short_description' => 'Old short desc.',
            'price' => 1000.00,
            'status' => CourseStatus::DRAFT,
            'thumbnail' => $oldPath,
        ]);

        $newThumbnail = UploadedFile::fake()->image('new.png');

        $updatePayload = [
            'title' => 'Updated Title Course',
            'slug' => 'updated-title-course',
            'short_description' => 'New short desc.',
            'is_free' => '0',
            'price' => '2000.00',
            'discount_price' => '1200.00',
            'status' => 'published',
            'thumbnail' => $newThumbnail,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.courses.update', $course), $updatePayload);

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success');

        $course->refresh();
        $this->assertEquals('Updated Title Course', $course->title);
        $this->assertEquals('updated-title-course', $course->slug);
        $this->assertEquals(2000.00, (float) $course->price);
        $this->assertTrue($course->isPublished());

        // Old thumbnail should be cleaned up, new thumbnail exists
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($course->thumbnail);
    }

    public function test_admin_can_delete_course_and_thumbnail_is_cleaned_up(): void
    {
        Storage::fake('public');

        $thumb = UploadedFile::fake()->image('to_delete.jpg');
        $path = $thumb->store('courses/thumbnails', 'public');

        $course = Course::create([
            'title' => 'Course To Delete',
            'slug' => 'course-to-delete',
            'short_description' => 'Will be deleted.',
            'thumbnail' => $path,
            'status' => CourseStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.courses.destroy', $course));

        $response->assertRedirect(route('admin.courses.index'));
        $this->assertDatabaseMissing('courses', ['id' => $course->id]);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_admin_can_manage_course_categories(): void
    {
        // 1. Create Category
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'name' => 'Email Strategies',
            'slug' => 'email-strategies',
            'description' => 'Email outreach and newsletter growth.',
            'status' => 'active',
        ]);
        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('course_categories', ['slug' => 'email-strategies']);

        $category = CourseCategory::where('slug', 'email-strategies')->first();

        // 2. Associate a course with this category
        $course = Course::create([
            'course_category_id' => $category->id,
            'title' => 'Newsletter Bootcamp',
            'slug' => 'newsletter-bootcamp',
            'short_description' => 'Build an email audience.',
            'status' => CourseStatus::PUBLISHED,
        ]);

        // 3. Update Category
        $updateResp = $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
            'name' => 'Email & Retention Marketing',
            'slug' => 'email-retention-marketing',
            'status' => 'active',
        ]);
        $updateResp->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('course_categories', ['slug' => 'email-retention-marketing']);

        // 4. Delete Category (Course should NOT be deleted, course_category_id should become NULL)
        $deleteResp = $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category));
        $deleteResp->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseMissing('course_categories', ['id' => $category->id]);
        $course->refresh();
        $this->assertNull($course->course_category_id);
    }

    public function test_courses_search_and_filter(): void
    {
        $cat1 = CourseCategory::create(['name' => 'Performance', 'slug' => 'performance']);
        $cat2 = CourseCategory::create(['name' => 'Content', 'slug' => 'content']);

        Course::create([
            'course_category_id' => $cat1->id,
            'title' => 'Facebook Ads Mastery',
            'slug' => 'fb-ads',
            'short_description' => 'Paid ads.',
            'status' => CourseStatus::PUBLISHED,
            'is_free' => false,
            'price' => 999.00,
        ]);

        Course::create([
            'course_category_id' => $cat2->id,
            'title' => 'Copywriting Secrets',
            'slug' => 'copywriting-secrets',
            'short_description' => 'Writing copy.',
            'status' => CourseStatus::DRAFT,
            'is_free' => true,
            'price' => 0.00,
        ]);

        // Search for Facebook
        $searchResp = $this->actingAs($this->admin)->get(route('admin.courses.index', ['search' => 'Facebook']));
        $searchResp->assertSee('Facebook Ads Mastery');
        $searchResp->assertDontSee('Copywriting Secrets');

        // Filter by Draft status
        $statusResp = $this->actingAs($this->admin)->get(route('admin.courses.index', ['status' => 'draft']));
        $statusResp->assertSee('Copywriting Secrets');
        $statusResp->assertDontSee('Facebook Ads Mastery');

        // Filter by Category
        $catResp = $this->actingAs($this->admin)->get(route('admin.courses.index', ['category_id' => $cat1->id]));
        $catResp->assertSee('Facebook Ads Mastery');
        $catResp->assertDontSee('Copywriting Secrets');
    }
}
