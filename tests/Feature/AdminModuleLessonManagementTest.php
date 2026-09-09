<?php

namespace Tests\Feature;

use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminModuleLessonManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Advanced Marketing Strategy',
            'slug' => 'advanced-marketing-strategy',
            'short_description' => 'A complete masterclass.',
        ]);
    }

    public function test_guest_and_student_are_blocked_from_module_routes(): void
    {
        // Guest
        $response = $this->get(route('admin.courses.modules.index', $this->course));
        $response->assertRedirect(route('login'));

        // Student
        $studentResponse = $this->actingAs($this->student)->get(route('admin.courses.modules.index', $this->course));
        $studentResponse->assertStatus(403);
    }

    public function test_admin_can_view_modules_and_create_form(): void
    {
        CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Foundations of Sales Funnels',
            'sort_order' => 1,
        ]);

        $indexResp = $this->actingAs($this->admin)->get(route('admin.courses.modules.index', $this->course));
        $indexResp->assertStatus(200);
        $indexResp->assertSee('Foundations of Sales Funnels');
        $indexResp->assertSee('Curriculum Modules');

        $createResp = $this->actingAs($this->admin)->get(route('admin.courses.modules.create', $this->course));
        $createResp->assertStatus(200);
        $createResp->assertSee('Add New Module');
    }

    public function test_admin_can_create_module_with_auto_calculated_sort_order(): void
    {
        $payload1 = [
            'title' => 'Module 1: Market Research',
            'description' => 'Research target audiences.',
        ];

        $resp1 = $this->actingAs($this->admin)->post(route('admin.courses.modules.store', $this->course), $payload1);
        $resp1->assertRedirect(route('admin.courses.modules.index', $this->course));

        $this->assertDatabaseHas('course_modules', [
            'course_id' => $this->course->id,
            'title' => 'Module 1: Market Research',
            'sort_order' => 1,
        ]);

        $payload2 = [
            'title' => 'Module 2: Customer Acquisition',
            'description' => 'Acquire leads effectively.',
        ];

        $resp2 = $this->actingAs($this->admin)->post(route('admin.courses.modules.store', $this->course), $payload2);
        $resp2->assertRedirect(route('admin.courses.modules.index', $this->course));

        $this->assertDatabaseHas('course_modules', [
            'course_id' => $this->course->id,
            'title' => 'Module 2: Customer Acquisition',
            'sort_order' => 2,
        ]);
    }

    public function test_admin_can_update_module(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Draft Title Module',
            'sort_order' => 1,
        ]);

        $updatePayload = [
            'title' => 'Final Brand Strategy Module',
            'description' => 'Updated syllabus description.',
            'sort_order' => 5,
        ];

        $resp = $this->actingAs($this->admin)->put(route('admin.courses.modules.update', [$this->course, $module]), $updatePayload);
        $resp->assertRedirect(route('admin.courses.modules.index', $this->course));

        $module->refresh();
        $this->assertEquals('Final Brand Strategy Module', $module->title);
        $this->assertEquals(5, $module->sort_order);
    }

    public function test_admin_can_delete_module_and_it_cascades_to_lessons(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module To Remove',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson In Removed Module',
            'slug' => 'lesson-in-removed-module',
            'lesson_type' => LessonType::VIDEO,
        ]);

        $resp = $this->actingAs($this->admin)->delete(route('admin.courses.modules.destroy', [$this->course, $module]));
        $resp->assertRedirect(route('admin.courses.modules.index', $this->course));

        $this->assertDatabaseMissing('course_modules', ['id' => $module->id]);
        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
    }

    public function test_cross_course_module_tampering_is_prevented(): void
    {
        $otherCourse = Course::create([
            'title' => 'Another Course',
            'slug' => 'another-course',
            'short_description' => 'Short desc.',
        ]);

        $otherModule = CourseModule::create([
            'course_id' => $otherCourse->id,
            'title' => 'Other Course Module',
            'sort_order' => 1,
        ]);

        // Attempting to edit otherCourse's module under $this->course should 404
        $response = $this->actingAs($this->admin)->get(route('admin.courses.modules.edit', [$this->course, $otherModule]));
        $response->assertStatus(404);
    }

    public function test_admin_can_create_video_lesson(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Video Module',
            'sort_order' => 1,
        ]);

        $payload = [
            'title' => 'Intro to Social Media Strategy',
            'slug' => 'intro-to-social-media-strategy',
            'lesson_type' => 'video',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'duration' => '12 mins',
            'is_preview' => '1',
            'status' => 'published',
            'sort_order' => '1',
        ];

        $resp = $this->actingAs($this->admin)->post(route('admin.courses.modules.lessons.store', [$this->course, $module]), $payload);
        $resp->assertRedirect(route('admin.courses.modules.lessons.index', [$this->course, $module]));

        $this->assertDatabaseHas('lessons', [
            'course_module_id' => $module->id,
            'title' => 'Intro to Social Media Strategy',
            'slug' => 'intro-to-social-media-strategy',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'is_preview' => 1,
            'status' => 'published',
        ]);
    }

    public function test_admin_can_create_text_lesson(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Text Reading Module',
            'sort_order' => 1,
        ]);

        $payload = [
            'title' => 'Copywriting Hook Formulas',
            'lesson_type' => 'text',
            'content' => 'Here are the top 5 hook formulas for business founders...',
            'duration' => '8 mins',
            'is_preview' => '0',
            'status' => 'published',
        ];

        $resp = $this->actingAs($this->admin)->post(route('admin.courses.modules.lessons.store', [$this->course, $module]), $payload);
        $resp->assertRedirect(route('admin.courses.modules.lessons.index', [$this->course, $module]));

        $this->assertDatabaseHas('lessons', [
            'course_module_id' => $module->id,
            'title' => 'Copywriting Hook Formulas',
            'lesson_type' => 'text',
            'is_preview' => 0,
        ]);
    }

    public function test_admin_can_create_and_manage_pdf_lesson(): void
    {
        Storage::fake('public');

        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Resource Module',
            'sort_order' => 1,
        ]);

        $pdfFile = UploadedFile::fake()->create('marketing_checklist.pdf', 500, 'application/pdf');

        $payload = [
            'title' => 'Downloadable Launch Checklist',
            'lesson_type' => 'pdf',
            'pdf_file' => $pdfFile,
            'duration' => '5 mins',
            'status' => 'published',
        ];

        $resp = $this->actingAs($this->admin)->post(route('admin.courses.modules.lessons.store', [$this->course, $module]), $payload);
        $resp->assertRedirect(route('admin.courses.modules.lessons.index', [$this->course, $module]));

        $lesson = Lesson::where('title', 'Downloadable Launch Checklist')->first();
        $this->assertNotNull($lesson);
        $this->assertNotNull($lesson->pdf_url);
        Storage::disk('public')->assertExists($lesson->pdf_url);

        // Update with new PDF and ensure old is purged
        $oldPdfPath = $lesson->pdf_url;
        $newPdfFile = UploadedFile::fake()->create('revised_checklist.pdf', 600, 'application/pdf');

        $updateResp = $this->actingAs($this->admin)->put(
            route('admin.courses.modules.lessons.update', [$this->course, $module, $lesson]),
            [
                'title' => 'Revised Launch Checklist',
                'lesson_type' => 'pdf',
                'pdf_file' => $newPdfFile,
                'status' => 'published',
            ]
        );
        $updateResp->assertRedirect(route('admin.courses.modules.lessons.index', [$this->course, $module]));

        $lesson->refresh();
        Storage::disk('public')->assertMissing($oldPdfPath);
        Storage::disk('public')->assertExists($lesson->pdf_url);

        // Delete lesson and ensure PDF is cleaned from storage
        $deleteResp = $this->actingAs($this->admin)->delete(
            route('admin.courses.modules.lessons.destroy', [$this->course, $module, $lesson])
        );
        $deleteResp->assertRedirect(route('admin.courses.modules.lessons.index', [$this->course, $module]));

        $this->assertDatabaseMissing('lessons', ['id' => $lesson->id]);
        Storage::disk('public')->assertMissing($lesson->pdf_url);
    }

    public function test_cross_module_lesson_tampering_is_prevented(): void
    {
        $module1 = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $module2 = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 2',
            'sort_order' => 2,
        ]);

        $lessonInModule2 = Lesson::create([
            'course_module_id' => $module2->id,
            'title' => 'Lesson In Mod 2',
            'slug' => 'lesson-in-mod-2',
            'lesson_type' => LessonType::TEXT,
        ]);

        // Accessing lesson from module 2 with URL containing module 1 should 404
        $resp = $this->actingAs($this->admin)->get(
            route('admin.courses.modules.lessons.edit', [$this->course, $module1, $lessonInModule2])
        );
        $resp->assertStatus(404);
    }
}
