<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected CourseCategory $category;
    protected Course $course;
    protected Course $paidCourse;
    protected CourseModule $module;
    protected Lesson $lesson1;
    protected Lesson $lesson2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'name' => 'Alice Founder',
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'name' => 'Bob Merchant',
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Social Media Strategy for Founders',
            'slug' => 'social-media-strategy-for-founders',
            'short_description' => 'Learn practical social media acquisition.',
            'instructor_name' => 'John Instructor',
            'is_free' => true,
            'price' => 0.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->paidCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Advanced Growth Marketing',
            'slug' => 'advanced-growth-marketing',
            'short_description' => 'High-level scale and analytics.',
            'instructor_name' => 'Sarah Scale',
            'is_free' => false,
            'price' => 4999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1: Strategy',
            'sort_order' => 1,
        ]);

        $this->lesson1 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 1: Positioning',
            'slug' => 'lesson-1-positioning',
            'lesson_type' => LessonType::VIDEO,
            'duration' => '10 mins',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
        ]);

        $this->lesson2 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 2: Distribution',
            'slug' => 'lesson-2-distribution',
            'lesson_type' => LessonType::TEXT,
            'content' => 'Comprehensive framework on distribution channels.',
            'duration' => '15 mins',
            'sort_order' => 2,
            'status' => LessonStatus::PUBLISHED,
        ]);
    }

    /**
     * Helper to complete the course for a student.
     */
    protected function completeCourseFor(User $user, Course $course): Enrollment
    {
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['status' => EnrollmentStatus::ACTIVE, 'enrolled_at' => now()]
        );

        $lessons = Lesson::whereHas('module', function ($q) use ($course) {
            $q->where('course_id', $course->id);
        })->get();

        foreach ($lessons as $lesson) {
            LessonProgress::updateOrCreate(
                ['user_id' => $user->id, 'lesson_id' => $lesson->id],
                ['completed' => true, 'completed_at' => now(), 'last_watched_at' => now()]
            );
        }

        $enrollment->markAsCompleted();

        return $enrollment;
    }

    /**
     * Test 1: Guest cannot access certificates.
     */
    public function test_guest_cannot_access_certificates(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $response = $this->get(route('student.certificates.show', $certificate));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test 2: Authenticated student can access their own certificate.
     */
    public function test_authenticated_student_can_access_their_own_certificate(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $response = $this->actingAs($this->student)->get(route('student.certificates.show', $certificate));

        $response->assertStatus(200);
        $response->assertSee('Alice Founder');
        $response->assertSee('Social Media Strategy for Founders');
        $response->assertSee($certificate->certificate_number);
        $response->assertSee('Certificate of Completion', false);
        $response->assertSee('John Instructor');
    }

    /**
     * Test 3: Student cannot access another student's certificate (403 Forbidden).
     */
    public function test_student_cannot_access_another_students_certificate(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        // Bob Merchant attempts to access Alice Founder's certificate
        $response = $this->actingAs($this->otherStudent)->get(route('student.certificates.show', $certificate));
        $response->assertStatus(403);
    }

    /**
     * Test 4: Certificate cannot be issued before course completion.
     */
    public function test_certificate_cannot_be_issued_before_course_completion(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete only 1 of 2 lessons (50% progress)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);
        $this->assertNull($certificate);
        $this->assertDatabaseMissing('certificates', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }

    /**
     * Test 5: Certificate is issued when course is genuinely completed.
     */
    public function test_certificate_is_issued_when_course_is_genuinely_completed(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete lesson 1
        $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));
        $this->assertDatabaseMissing('certificates', ['user_id' => $this->student->id]);

        // Complete lesson 2 (final lesson)
        $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson2]));

        $this->assertDatabaseHas('certificates', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);

        $certificate = Certificate::where('user_id', $this->student->id)->where('course_id', $this->course->id)->first();
        $this->assertNotNull($certificate);
    }

    /**
     * Test 6: Certificate is issued only for the correct student.
     */
    public function test_certificate_is_issued_only_for_the_correct_student(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->assertEquals($this->student->id, $certificate->user_id);
        $this->assertEquals('Alice Founder', $certificate->student_name);
        $this->assertNotEquals($this->otherStudent->id, $certificate->user_id);
    }

    /**
     * Test 7: Certificate is linked to the correct course.
     */
    public function test_certificate_is_linked_to_the_correct_course(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->assertEquals($this->course->id, $certificate->course_id);
        $this->assertEquals('Social Media Strategy for Founders', $certificate->course_title);
    }

    /**
     * Test 8: Certificate is linked to the correct enrollment.
     */
    public function test_certificate_is_linked_to_the_correct_enrollment(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->assertEquals($enrollment->id, $certificate->enrollment_id);
    }

    /**
     * Test 9: Certificate number is generated server-side.
     */
    public function test_certificate_number_is_generated_server_side(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->assertNotNull($certificate->certificate_number);
        $this->assertMatchesRegularExpression('/^MM-\d{4}-[A-Z0-9]{8}$/', $certificate->certificate_number);
    }

    /**
     * Test 10: Certificate number is unique.
     */
    public function test_certificate_number_is_unique(): void
    {
        $enrollment1 = $this->completeCourseFor($this->student, $this->course);
        $cert1 = Certificate::issueFor($this->student, $this->course, $enrollment1);

        $enrollment2 = $this->completeCourseFor($this->otherStudent, $this->course);
        $cert2 = Certificate::issueFor($this->otherStudent, $this->course, $enrollment2);

        $this->assertNotEquals($cert1->certificate_number, $cert2->certificate_number);
    }

    /**
     * Test 11: Duplicate completion does not create duplicate certificates.
     */
    public function test_duplicate_completion_does_not_create_duplicate_certificates(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete lesson 1
        $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]));
        // Complete lesson 2
        $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson2]));

        $this->assertEquals(1, Certificate::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());

        // Call complete again
        $this->actingAs($this->student)->post(route('student.courses.lessons.complete', [$this->course, $this->lesson2]));
        $this->assertEquals(1, Certificate::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());
    }

    /**
     * Test 12: Database unique constraint prevents duplicate certificates.
     */
    public function test_database_unique_constraint_prevents_duplicate_certificates(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->expectException(QueryException::class);

        // Attempt raw direct insert violating unique(user_id, course_id)
        Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => Certificate::generateCertificateNumber(),
            'course_title' => 'Dupe Test',
            'student_name' => 'Alice Founder',
            'course_completion_date' => now(),
            'issued_at' => now(),
        ]);
    }

    /**
     * Test 13: Certificate stores correct historical student name.
     */
    public function test_certificate_stores_correct_historical_student_name(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->assertEquals('Alice Founder', $certificate->student_name);

        // Student updates name in user profile later
        $this->student->update(['name' => 'Alice Renamed']);

        // Historical certificate record remains unchanged
        $this->assertEquals('Alice Founder', $certificate->fresh()->student_name);
    }

    /**
     * Test 14: Certificate stores correct historical course title.
     */
    public function test_certificate_stores_correct_historical_course_title(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->assertEquals('Social Media Strategy for Founders', $certificate->course_title);

        // Course title is updated by admin later
        $this->course->update(['title' => 'Social Media Strategy 2.0']);

        // Historical certificate record preserves title at issuance time
        $this->assertEquals('Social Media Strategy for Founders', $certificate->fresh()->course_title);
    }

    /**
     * Test 15: Certificate stores correct completion date.
     */
    public function test_certificate_stores_correct_completion_date(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $this->assertNotNull($certificate->course_completion_date);
        $this->assertNotNull($certificate->issued_at);
    }

    /**
     * Test 16: Completed course shows View Certificate.
     */
    public function test_completed_course_shows_view_certificate_in_learning_view(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson2]));

        $response->assertStatus(200);
        $response->assertSee('View Certificate');
        $response->assertSee(route('student.certificates.show', $certificate));
    }

    /**
     * Test 17: Incomplete course does not show View Certificate.
     */
    public function test_incomplete_course_does_not_show_view_certificate(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));

        $response->assertStatus(200);
        $response->assertDontSee('View Certificate');
    }

    /**
     * Test 18: Student dashboard certificate CTA works.
     */
    public function test_student_dashboard_certificate_cta_works(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('View Certificate');
        $response->assertSee(route('student.certificates.show', $certificate));
    }

    /**
     * Test 19: My Courses certificate CTA works.
     */
    public function test_my_courses_certificate_cta_works(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $response = $this->actingAs($this->student)->get(route('student.courses.index'));

        $response->assertStatus(200);
        $response->assertSee('View Certificate');
        $response->assertSee(route('student.certificates.show', $certificate));
    }

    /**
     * Test 20: Course detail certificate CTA works.
     */
    public function test_course_detail_certificate_cta_works(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        $response = $this->actingAs($this->student)->get(route('courses.show', $this->course));

        $response->assertStatus(200);
        $response->assertSee('View Certificate');
        $response->assertSee(route('student.certificates.show', $certificate));
    }

    /**
     * Test 21: Certificate cannot be manipulated through request parameters.
     */
    public function test_certificate_cannot_be_manipulated_through_request_parameters(): void
    {
        $enrollment = $this->completeCourseFor($this->student, $this->course);
        $certificate = Certificate::issueFor($this->student, $this->course, $enrollment);

        // Attempting to access another user's certificate ID or passing forged params
        $response = $this->actingAs($this->otherStudent)->get(route('student.certificates.show', [
            'certificate' => $certificate->id,
            'user_id' => $this->otherStudent->id,
        ]));

        $response->assertStatus(403);
    }

    /**
     * Test 22: Existing learning/progress functionality remains unaffected.
     */
    public function test_existing_learning_progress_functionality_remains_unaffected(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course, $this->lesson1]));
        $response->assertStatus(200);
        $response->assertSee('Mark as Complete');
        $response->assertSee('Lesson 1: Positioning');
    }

    /**
     * Test 23: Existing enrollment functionality remains unaffected.
     */
    public function test_existing_enrollment_functionality_remains_unaffected(): void
    {
        $response = $this->actingAs($this->otherStudent)->post(route('student.courses.enroll', $this->course));
        $response->assertRedirect(route('student.courses.show', $this->course));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->otherStudent->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);
    }

    /**
     * Test 24: Existing payment functionality remains unaffected.
     */
    public function test_existing_payment_functionality_remains_unaffected(): void
    {
        $response = $this->actingAs($this->student)->post(route('student.courses.purchase', $this->paidCourse));
        $order = Order::where('user_id', $this->student->id)->where('course_id', $this->paidCourse->id)->first();

        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::PENDING, $order->status);
        $response->assertRedirect(route('student.courses.checkout', $order));

        $checkoutResponse = $this->actingAs($this->student)->get(route('student.courses.checkout', $order));
        $checkoutResponse->assertStatus(200);
        $checkoutResponse->assertSee('Advanced Growth Marketing');
    }
}