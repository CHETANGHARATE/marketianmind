<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\UserRole;
use App\Models\CourseCategory;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonResourceTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected Course $course;
    protected CourseModule $module;
    protected Lesson $lesson;

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
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery',
            'short_description' => 'Comprehensive masterclass.',
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1: Fundamentals',
            'sort_order' => 1,
        ]);

        $this->lesson = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Introduction to Market Research',
            'slug' => 'intro-market-research',
            'lesson_type' => LessonType::VIDEO,
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);
    }

    public function test_admin_can_view_lesson_resources_page(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.courses.modules.lessons.resources.index', [$this->course, $this->module, $this->lesson])
        );

        $response->assertOk();
        $response->assertSee('Resources &amp; Downloads', false);
        $response->assertSee($this->lesson->title);
        $response->assertSee('No resources attached yet');
    }

    public function test_admin_can_add_downloadable_file_resource(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('worksheet.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(
            route('admin.courses.modules.lessons.resources.store', [$this->course, $this->module, $this->lesson]),
            [
                'title' => 'Market Research Worksheet',
                'type' => 'file',
                'file' => $file,
                'description' => 'Use this PDF worksheet during the video session.',
                'sort_order' => 1,
            ]
        );

        $response->assertRedirect(
            route('admin.courses.modules.lessons.resources.index', [$this->course, $this->module, $this->lesson])
        );
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lesson_resources', [
            'lesson_id' => $this->lesson->id,
            'title' => 'Market Research Worksheet',
            'type' => 'file',
            'file_name' => 'worksheet.pdf',
            'file_type' => 'pdf',
            'sort_order' => 1,
        ]);

        $resource = LessonResource::first();
        $this->assertNotNull($resource->file_path);
        Storage::disk('public')->assertExists($resource->file_path);
        $this->assertNotEmpty($resource->formattedSize());
    }

    public function test_admin_can_add_external_link_resource(): void
    {
        $response = $this->actingAs($this->admin)->post(
            route('admin.courses.modules.lessons.resources.store', [$this->course, $this->module, $this->lesson]),
            [
                'title' => 'Competitor Analysis Notion Template',
                'type' => 'link',
                'external_url' => 'https://notion.so/templates/market-research',
                'description' => 'Duplicate this template to your Notion workspace.',
                'sort_order' => 2,
            ]
        );

        $response->assertRedirect(
            route('admin.courses.modules.lessons.resources.index', [$this->course, $this->module, $this->lesson])
        );

        $this->assertDatabaseHas('lesson_resources', [
            'lesson_id' => $this->lesson->id,
            'title' => 'Competitor Analysis Notion Template',
            'type' => 'link',
            'external_url' => 'https://notion.so/templates/market-research',
            'file_path' => null,
            'file_name' => null,
            'sort_order' => 2,
        ]);
    }

    public function test_admin_cannot_upload_dangerous_executable_files(): void
    {
        Storage::fake('public');

        // Dangerous PHP script
        $dangerousFile = UploadedFile::fake()->create('exploit.php', 10, 'application/x-php');

        $response = $this->actingAs($this->admin)->post(
            route('admin.courses.modules.lessons.resources.store', [$this->course, $this->module, $this->lesson]),
            [
                'title' => 'Malicious File',
                'type' => 'file',
                'file' => $dangerousFile,
            ]
        );

        $response->assertSessionHasErrors(['file']);
        $this->assertDatabaseCount('lesson_resources', 0);
    }

    public function test_admin_can_delete_resource_and_stored_file_is_removed(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('checklist.xlsx', 200, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $filePath = $file->store("courses/{$this->course->id}/lessons/{$this->lesson->id}/resources", 'public');

        $resource = LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Ad Checklist',
            'type' => 'file',
            'file_path' => $filePath,
            'file_name' => 'checklist.xlsx',
            'file_size' => 204800,
            'file_type' => 'xlsx',
            'sort_order' => 1,
        ]);

        Storage::disk('public')->assertExists($filePath);

        $response = $this->actingAs($this->admin)->delete(
            route('admin.courses.modules.lessons.resources.destroy', [$this->course, $this->module, $this->lesson, $resource])
        );

        $response->assertRedirect(
            route('admin.courses.modules.lessons.resources.index', [$this->course, $this->module, $this->lesson])
        );
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('lesson_resources', ['id' => $resource->id]);
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_enrolled_student_can_see_resources_on_learning_page(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
        ]);

        LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Strategy Guidebook',
            'type' => 'file',
            'file_path' => 'dummy/path/guide.pdf',
            'file_name' => 'guide.pdf',
            'file_size' => 1048576, // 1 MB
            'file_type' => 'pdf',
            'description' => 'Comprehensive guidebook for strategy implementation.',
            'sort_order' => 1,
        ]);

        LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Official Case Study',
            'type' => 'link',
            'external_url' => 'https://example.com/case-study',
            'file_type' => 'link',
            'sort_order' => 2,
        ]);

        $response = $this->actingAs($this->student)->get(
            route('student.courses.lessons.show', [$this->course, $this->lesson])
        );

        $response->assertOk();
        $response->assertSee('Lesson Resources &amp; Downloads', false);
        $response->assertSee('Strategy Guidebook');
        $response->assertSee('Official Case Study');
        $response->assertSee('1 MB');
        $response->assertSee('Download File');
        $response->assertSee('Open Link');
    }

    public function test_enrolled_student_can_download_file_resource(): void
    {
        Storage::fake('public');

        $storedPath = 'courses/test/worksheet.pdf';
        Storage::disk('public')->put($storedPath, 'Sample PDF content');

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
        ]);

        $resource = LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Downloadable Worksheet',
            'type' => 'file',
            'file_path' => $storedPath,
            'file_name' => 'worksheet.pdf',
            'file_size' => 100,
            'file_type' => 'pdf',
        ]);

        $response = $this->actingAs($this->student)->get(
            route('student.courses.lessons.resources.download', [$this->course, $this->lesson, $resource])
        );

        $response->assertOk();
        $this->assertTrue($response->headers->get('content-disposition') !== null);
    }

    public function test_enrolled_student_redirected_to_external_link_resource(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
        ]);

        $resource = LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'External Tool',
            'type' => 'link',
            'external_url' => 'https://example.com/tool',
        ]);

        $response = $this->actingAs($this->student)->get(
            route('student.courses.lessons.resources.download', [$this->course, $this->lesson, $resource])
        );

        $response->assertRedirect('https://example.com/tool');
    }

    public function test_non_enrolled_student_cannot_download_resource(): void
    {
        Storage::fake('public');

        $resource = LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Secret Material',
            'type' => 'file',
            'file_path' => 'secret.pdf',
            'file_name' => 'secret.pdf',
        ]);

        // Student is NOT enrolled
        $response = $this->actingAs($this->student)->get(
            route('student.courses.lessons.resources.download', [$this->course, $this->lesson, $resource])
        );

        $response->assertForbidden();
    }

    public function test_student_cannot_access_resource_belonging_to_another_lesson_or_course(): void
    {
        Storage::fake('public');

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
        ]);

        // Another course and lesson
        $otherCourse = Course::create([
            'title' => 'Other Course',
            'slug' => 'other-course',
            'short_description' => 'Another course description.',
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);
        $otherModule = CourseModule::create([
            'course_id' => $otherCourse->id,
            'title' => 'Other Module',
            'sort_order' => 1,
        ]);
        $otherLesson = Lesson::create([
            'course_module_id' => $otherModule->id,
            'title' => 'Other Lesson',
            'slug' => 'other-lesson',
            'status' => LessonStatus::PUBLISHED,
        ]);
        $otherResource = LessonResource::create([
            'lesson_id' => $otherLesson->id,
            'title' => 'Other Resource',
            'type' => 'file',
            'file_path' => 'other.pdf',
        ]);

        // Attempt IDOR: accessing otherResource through course 1 & lesson 1
        $response = $this->actingAs($this->student)->get(
            route('student.courses.lessons.resources.download', [$this->course, $this->lesson, $otherResource])
        );

        $response->assertNotFound();
    }

    public function test_lesson_progress_tracking_works_seamlessly_with_resources(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrolled_at' => now(),
        ]);

        LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Attached Guide',
            'type' => 'file',
            'file_path' => 'guide.pdf',
        ]);

        $response = $this->actingAs($this->student)->post(
            route('student.courses.lessons.complete', [$this->course, $this->lesson])
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson->id,
            'completed' => true,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_accessing_resource_download(): void
    {
        $resource = LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Sample Guide',
            'type' => 'file',
            'file_path' => 'sample.pdf',
        ]);

        $response = $this->get(
            route('student.courses.lessons.resources.download', [$this->course, $this->lesson, $resource])
        );

        $response->assertRedirect(route('login'));
    }

    public function test_admin_hierarchy_validation_blocks_mismatched_relationships(): void
    {
        $unrelatedModule = CourseModule::create([
            'course_id' => Course::create([
                'title' => 'Different Course',
                'slug' => 'diff-course',
                'short_description' => 'Different course summary',
            ])->id,
            'title' => 'Unrelated Module',
        ]);

        // Accessing resources with unrelated module should 404
        $response = $this->actingAs($this->admin)->get(
            route('admin.courses.modules.lessons.resources.index', [$this->course, $unrelatedModule, $this->lesson])
        );

        $response->assertNotFound();
    }

    public function test_lesson_model_has_resources_helper_functions_correctly(): void
    {
        $this->assertFalse($this->lesson->hasResources());

        LessonResource::create([
            'lesson_id' => $this->lesson->id,
            'title' => 'Quick Reference',
            'type' => 'link',
            'external_url' => 'https://example.com',
        ]);

        $this->assertTrue($this->lesson->fresh()->hasResources());
        $this->assertEquals(1, $this->lesson->resources()->count());
    }
}