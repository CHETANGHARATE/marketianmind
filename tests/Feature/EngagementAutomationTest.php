<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Mail\CourseCompletionMail;
use App\Mail\CourseEnrollmentMail;
use App\Mail\StudentInactivityMail;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\EngagementLog;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\StudentLearningDay;
use App\Models\User;
use App\Notifications\CertificateAvailableNotification;
use App\Notifications\CourseCompletionNotification;
use App\Notifications\CourseEnrollmentNotification;
use App\Notifications\CourseProgressMilestoneNotification;
use App\Notifications\StudentInactivityReminderNotification;
use App\Services\EngagementService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EngagementAutomationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $course;
    protected CourseModule $module;
    protected Lesson $lesson1;
    protected Lesson $lesson2;
    protected Lesson $lesson3;
    protected Lesson $lesson4;
    protected EngagementService $engagementService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engagementService = app(EngagementService::class);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
            'name' => 'Engaged Student',
            'email' => 'engaged.student@example.com',
        ]);

        $this->course = Course::create([
            'title' => 'Social Media Automation Engine',
            'slug' => 'social-media-automation-engine',
            'short_description' => 'Automate organic marketing.',
            'price' => 0,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Core Module',
            'sort_order' => 1,
        ]);

        // 4 lessons = 25%, 50%, 75%, 100%
        $this->lesson1 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 1: Foundations',
            'slug' => 'lesson-1-foundations',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        $this->lesson2 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 2: Audience Insights',
            'slug' => 'lesson-2-audience-insights',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        $this->lesson3 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 3: Workflow Automation',
            'slug' => 'lesson-3-workflow-automation',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 3,
        ]);

        $this->lesson4 = Lesson::create([
            'course_module_id' => $this->module->id,
            'title' => 'Lesson 4: Final Campaign Launch',
            'slug' => 'lesson-4-final-campaign-launch',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 4,
        ]);
    }

    public function test_1_successful_paid_enrollment_triggers_communication(): void
    {
        Notification::fake();
        Mail::fake();

        $paidCourse = Course::create([
            'title' => 'Paid Growth Masterclass',
            'slug' => 'paid-growth-masterclass',
            'short_description' => 'Scale with paid channels.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $paidCourse->id,
            'order_number' => 'ORD-TEST-001',
            'razorpay_order_id' => 'order_valid_123',
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
            'gateway' => 'razorpay',
        ]);

        // Simulate successful payment verification callback
        $this->actingAs($this->student)
            ->post(route('payments.razorpay.verify'), [
                'razorpay_order_id' => 'order_valid_123',
                'razorpay_payment_id' => 'pay_valid_789',
                'razorpay_signature' => 'valid_test_signature',
            ]);

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $paidCourse->id,
            'type' => 'enrollment',
        ]);

        Notification::assertSentTo($this->student, CourseEnrollmentNotification::class);
        Mail::assertSent(CourseEnrollmentMail::class);
    }

    public function test_2_failed_payment_does_not_trigger_enrollment_communication(): void
    {
        Notification::fake();
        Mail::fake();

        $paidCourse = Course::create([
            'title' => 'Premium Strategy',
            'slug' => 'premium-strategy',
            'short_description' => 'Advanced strategies.',
            'price' => 9999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        Order::create([
            'user_id' => $this->student->id,
            'course_id' => $paidCourse->id,
            'order_number' => 'ORD-FAILED-001',
            'amount' => 9999.00,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
            'gateway' => 'razorpay',
        ]);

        $this->assertDatabaseMissing('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $paidCourse->id,
            'type' => 'enrollment',
        ]);

        Notification::assertNotSentTo($this->student, CourseEnrollmentNotification::class);
        Mail::assertNotSent(CourseEnrollmentMail::class);
    }

    public function test_3_free_enrollment_triggers_communication(): void
    {
        Notification::fake();
        Mail::fake();

        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->course))
            ->assertRedirect();

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'enrollment',
        ]);

        Notification::assertSentTo($this->student, CourseEnrollmentNotification::class);
        Mail::assertSent(CourseEnrollmentMail::class);
    }

    public function test_4_duplicate_enrollment_processing_does_not_send_duplicates(): void
    {
        Notification::fake();
        Mail::fake();

        $this->engagementService->handleEnrollment($this->student, $this->course);
        $result = $this->engagementService->handleEnrollment($this->student, $this->course);

        $this->assertFalse($result);
        $this->assertEquals(1, EngagementLog::where('user_id', $this->student->id)->where('type', 'enrollment')->count());
        Notification::assertSentTimes(CourseEnrollmentNotification::class, 1);
        Mail::assertSent(CourseEnrollmentMail::class, 1);
    }

    public function test_5_milestone_25_triggers_once(): void
    {
        Notification::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete lesson 1 of 4 (25%)
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]))
            ->assertRedirect();

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'milestone_25',
        ]);

        Notification::assertSentTo(
            $this->student,
            CourseProgressMilestoneNotification::class,
            fn ($n) => $n->milestone === 'milestone_25' && $n->percentage === 25
        );
    }

    public function test_6_milestone_50_triggers_once(): void
    {
        Notification::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // Complete lesson 2 of 4 (50%)
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson2]))
            ->assertRedirect();

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'milestone_50',
        ]);

        Notification::assertSentTo(
            $this->student,
            CourseProgressMilestoneNotification::class,
            fn ($n) => $n->milestone === 'milestone_50' && $n->percentage === 50
        );
    }

    public function test_7_milestone_75_triggers_once(): void
    {
        Notification::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        foreach ([$this->lesson1, $this->lesson2] as $l) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $l->id,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        // Complete lesson 3 of 4 (75%)
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson3]))
            ->assertRedirect();

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'milestone_75',
        ]);

        Notification::assertSentTo(
            $this->student,
            CourseProgressMilestoneNotification::class,
            fn ($n) => $n->milestone === 'milestone_75' && $n->percentage === 75
        );
    }

    public function test_8_near_completion_90_triggers_once(): void
    {
        Notification::fake();

        // Create a 10-lesson course so lesson 9 is 90%
        $bigCourse = Course::create([
            'title' => 'Extensive Digital Track',
            'slug' => 'extensive-digital-track',
            'short_description' => 'Complete curriculum.',
            'price' => 0,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);
        $mod = CourseModule::create([
            'course_id' => $bigCourse->id,
            'title' => 'Big Module',
            'sort_order' => 1,
        ]);
        $lessons = [];
        for ($i = 1; $i <= 10; $i++) {
            $lessons[] = Lesson::create([
                'course_module_id' => $mod->id,
                'title' => "Big Lesson $i",
                'slug' => "big-lesson-$i",
                'status' => LessonStatus::PUBLISHED,
                'sort_order' => $i,
            ]);
        }

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $bigCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        for ($i = 0; $i < 8; $i++) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $lessons[$i]->id,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        // Complete 9th lesson of 10 (90%)
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$bigCourse, $lessons[8]]))
            ->assertRedirect();

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $bigCourse->id,
            'type' => 'near_completion',
        ]);

        Notification::assertSentTo(
            $this->student,
            CourseProgressMilestoneNotification::class,
            fn ($n) => $n->milestone === 'near_completion' && $n->percentage === 90
        );
    }

    public function test_9_course_completion_triggers_notification_and_email(): void
    {
        Notification::fake();
        Mail::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        foreach ([$this->lesson1, $this->lesson2, $this->lesson3] as $l) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $l->id,
                'completed' => true,
                'completed_at' => now(),
            ]);
        }

        // Complete final lesson 4
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson4]))
            ->assertRedirect();

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'completion',
        ]);

        Notification::assertSentTo($this->student, CourseCompletionNotification::class);
        Mail::assertSent(CourseCompletionMail::class);
    }

    public function test_10_duplicate_course_completion_does_not_send_duplicate_communications(): void
    {
        Notification::fake();
        Mail::fake();

        $this->engagementService->handleCourseCompletion($this->student, $this->course);
        $result = $this->engagementService->handleCourseCompletion($this->student, $this->course);

        $this->assertFalse($result);
        $this->assertEquals(1, EngagementLog::where('user_id', $this->student->id)->where('type', 'completion')->count());
        Notification::assertSentTimes(CourseCompletionNotification::class, 1);
        Mail::assertSent(CourseCompletionMail::class, 1);
    }

    public function test_11_certificate_availability_triggers_notification_and_email(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now(),
            'completed_at' => now(),
        ]);

        $certificate = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'MM-TEST-CERT-01',
            'student_name' => $this->student->name,
            'course_title' => $this->course->title,
            'issued_at' => now(),
            'course_completion_date' => now(),
            'verification_hash' => hash('sha256', 'MM-TEST-CERT-01'),
        ]);

        $this->engagementService->handleCertificateAvailable($this->student, $certificate);

        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'certificate',
        ]);

        Notification::assertSentTo($this->student, CertificateAvailableNotification::class);
    }

    public function test_12_certificate_notification_is_not_duplicated(): void
    {
        Notification::fake();
        Mail::fake();

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now(),
        ]);

        $certificate = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'MM-TEST-CERT-02',
            'student_name' => $this->student->name,
            'course_title' => $this->course->title,
            'issued_at' => now(),
            'course_completion_date' => now(),
            'verification_hash' => hash('sha256', 'MM-TEST-CERT-02'),
        ]);

        $this->engagementService->handleCertificateAvailable($this->student, $certificate);
        $result = $this->engagementService->handleCertificateAvailable($this->student, $certificate);

        $this->assertFalse($result);
        $this->assertEquals(1, EngagementLog::where('user_id', $this->student->id)->where('type', 'certificate')->count());
        Notification::assertSentTimes(CertificateAvailableNotification::class, 1);
    }

    public function test_13_eligible_inactive_student_receives_inactivity_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(20),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now()->subDays(10),
        ]);

        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => Carbon::today()->subDays(10)->toDateString(),
            'activity_type' => 'lesson_completed',
        ]);

        $count = $this->engagementService->processInactivityReminders();

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('engagement_logs', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'inactivity_reminder',
        ]);

        Notification::assertSentTo($this->student, StudentInactivityReminderNotification::class);
        Mail::assertSent(StudentInactivityMail::class);
    }

    public function test_14_active_student_does_not_receive_inactivity_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(10),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now()->subDays(2),
        ]);

        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => Carbon::today()->subDays(2)->toDateString(),
            'activity_type' => 'lesson_completed',
        ]);

        $count = $this->engagementService->processInactivityReminders();

        $this->assertEquals(0, $count);
        Notification::assertNotSentTo($this->student, StudentInactivityReminderNotification::class);
    }

    public function test_15_student_without_in_progress_course_does_not_receive_reminder(): void
    {
        Notification::fake();
        Mail::fake();

        // Enrolled but never completed any lesson (0 lessons completed)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(20),
        ]);

        $count = $this->engagementService->processInactivityReminders();

        $this->assertEquals(0, $count);
        Notification::assertNotSentTo($this->student, StudentInactivityReminderNotification::class);
    }

    public function test_16_inactivity_cooldown_prevents_repeated_reminders(): void
    {
        Notification::fake();
        Mail::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(20),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now()->subDays(10),
        ]);

        // Already received reminder 3 days ago (within 7-day cooldown)
        EngagementLog::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'inactivity_reminder',
            'channel' => 'both',
            'sent_at' => now()->subDays(3),
        ]);

        $count = $this->engagementService->processInactivityReminders();

        $this->assertEquals(0, $count);
        Notification::assertNotSentTo($this->student, StudentInactivityReminderNotification::class);
    }

    public function test_17_multiple_inactive_courses_selects_single_course_without_spam(): void
    {
        Notification::fake();
        Mail::fake();

        $courseB = Course::create([
            'title' => 'Email Marketing Mastery',
            'slug' => 'email-marketing-mastery',
            'short_description' => 'Email campaigns.',
            'price' => 0,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);
        $modB = CourseModule::create([
            'course_id' => $courseB->id,
            'title' => 'Mod B',
            'sort_order' => 1,
        ]);
        $lessonB1 = Lesson::create([
            'course_module_id' => $modB->id,
            'title' => 'Email Lesson 1',
            'slug' => 'email-lesson-1',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);
        $lessonB2 = Lesson::create([
            'course_module_id' => $modB->id,
            'title' => 'Email Lesson 2',
            'slug' => 'email-lesson-2',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        // Enroll in Course A (25% progress - 1/4)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(20),
        ]);
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now()->subDays(15),
        ]);

        // Enroll in Course B (50% progress - 1/2) -> Course B has higher progress!
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $courseB->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(20),
        ]);
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lessonB1->id,
            'completed' => true,
            'completed_at' => now()->subDays(14),
        ]);

        StudentLearningDay::create([
            'user_id' => $this->student->id,
            'activity_date' => Carbon::today()->subDays(14)->toDateString(),
            'activity_type' => 'lesson_completed',
        ]);

        $count = $this->engagementService->processInactivityReminders();

        $this->assertEquals(1, $count);
        // Only 1 reminder sent, targeting the higher progress Course B
        Notification::assertSentTimes(StudentInactivityReminderNotification::class, 1);
        Notification::assertSentTo(
            $this->student,
            StudentInactivityReminderNotification::class,
            fn ($n) => $n->course->id === $courseB->id
        );
    }

    public function test_18_recommended_course_is_published_and_not_already_enrolled(): void
    {
        $publishedCategory = CourseCategory::create([
            'name' => 'Growth Hacking',
            'slug' => 'growth-hacking',
        ]);

        $this->course->update(['category_id' => $publishedCategory->id]);

        $alreadyEnrolledCourse = Course::create([
            'category_id' => $publishedCategory->id,
            'title' => 'Enrolled Sibling',
            'slug' => 'enrolled-sibling',
            'short_description' => 'Already enrolled.',
            'price' => 0,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $alreadyEnrolledCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $recommendedCandidate = Course::create([
            'category_id' => $publishedCategory->id,
            'title' => 'Target Recommendation',
            'slug' => 'target-recommendation',
            'short_description' => 'Ideal next course.',
            'price' => 0,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $nextCourse = $this->engagementService->getRecommendedNextCourse($this->student, $this->course);

        $this->assertNotNull($nextCourse);
        $this->assertEquals($recommendedCandidate->id, $nextCourse->id);
    }

    public function test_19_student_data_isolation_in_engagement_communications(): void
    {
        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);

        EngagementLog::create([
            'user_id' => $otherStudent->id,
            'course_id' => $this->course->id,
            'type' => 'milestone_50',
            'channel' => 'database',
            'sent_at' => now(),
        ]);

        $this->assertDatabaseMissing('engagement_logs', [
            'user_id' => $this->student->id,
            'type' => 'milestone_50',
        ]);
    }

    public function test_20_engagement_process_command_is_idempotent(): void
    {
        Notification::fake();
        Mail::fake();

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(20),
        ]);

        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now()->subDays(10),
        ]);

        // Run artisan command twice in succession
        $this->artisan('engagement:process')->assertSuccessful();
        $this->artisan('engagement:process')->assertSuccessful();

        $this->assertEquals(1, EngagementLog::where('user_id', $this->student->id)->where('type', 'inactivity_reminder')->count());
        Notification::assertSentTimes(StudentInactivityReminderNotification::class, 1);
    }

    public function test_21_failed_email_does_not_break_learning_or_completion(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Mock mail failure
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP Connection Timeout'));

        // Lesson complete continues gracefully
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->course, $this->lesson1]))
            ->assertRedirect();

        $this->assertDatabaseHas('lesson_progress', [
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
        ]);
    }

    public function test_22_milestone_not_sent_again_on_incremental_progress(): void
    {
        Notification::fake();

        // 25% milestone already sent
        EngagementLog::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'type' => 'milestone_25',
            'channel' => 'database',
            'sent_at' => now(),
        ]);

        $res = $this->engagementService->handleProgressMilestones($this->student, $this->course, 28);

        $this->assertNull($res);
        Notification::assertNothingSent();
    }
}
