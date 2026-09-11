<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use App\Notifications\CourseEnrollmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $freeCourse;
    protected Course $paidCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->freeCourse = Course::create([
            'title' => 'Foundations of Marketing Strategy',
            'slug' => 'foundations-marketing-' . uniqid(),
            'short_description' => 'A free introductory marketing framework.',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->paidCourse = Course::create([
            'title' => 'Advanced Customer Acquisition Masterclass',
            'slug' => 'advanced-acquisition-' . uniqid(),
            'short_description' => 'Scale your revenue with paid traffic.',
            'price' => 3999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_guest_cannot_access_notifications_and_is_redirected_to_login(): void
    {
        $response = $this->get(route('student.notifications.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_can_view_empty_notifications_page(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.notifications.index'));

        $response->assertOk();
        $response->assertSee('Notifications');
        $response->assertSee('No notifications yet');
    }

    public function test_student_receives_enrollment_notification_upon_free_enrollment(): void
    {
        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->freeCourse));

        $this->assertEquals(1, $this->student->notifications()->count());

        $notification = $this->student->notifications()->first();
        $this->assertEquals('enrollment', $notification->data['type']);
        $this->assertStringContainsString($this->freeCourse->title, $notification->data['title']);
    }

    public function test_student_can_mark_notification_as_read(): void
    {
        $this->student->notify(new CourseEnrollmentNotification($this->freeCourse));
        $notification = $this->student->notifications()->first();

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($this->student)
            ->patch(route('student.notifications.read', $notification->id));

        $response->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_student_can_mark_all_notifications_as_read(): void
    {
        $this->student->notify(new CourseEnrollmentNotification($this->freeCourse));
        $this->student->notify(new CourseEnrollmentNotification($this->paidCourse));

        $this->assertEquals(2, $this->student->unreadNotifications()->count());

        $response = $this->actingAs($this->student)
            ->post(route('student.notifications.markAllRead'));

        $response->assertRedirect();
        $this->assertEquals(0, $this->student->unreadNotifications()->count());
    }

    public function test_student_can_delete_individual_notification(): void
    {
        $this->student->notify(new CourseEnrollmentNotification($this->freeCourse));
        $notification = $this->student->notifications()->first();

        $response = $this->actingAs($this->student)
            ->delete(route('student.notifications.destroy', $notification->id));

        $response->assertRedirect();
        $this->assertEquals(0, $this->student->notifications()->count());
    }

    public function test_student_cannot_access_or_modify_another_students_notification(): void
    {
        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);
        $otherStudent->notify(new CourseEnrollmentNotification($this->freeCourse));
        $otherNotification = $otherStudent->notifications()->first();

        // Attempt mark as read
        $response1 = $this->actingAs($this->student)
            ->patch(route('student.notifications.read', $otherNotification->id));
        $response1->assertNotFound();

        // Attempt delete
        $response2 = $this->actingAs($this->student)
            ->delete(route('student.notifications.destroy', $otherNotification->id));
        $response2->assertNotFound();

        // Ensure not marked read
        $this->assertNull($otherNotification->fresh()->read_at);
    }

    public function test_student_can_filter_by_unread_notifications(): void
    {
        $this->student->notify(new CourseEnrollmentNotification($this->freeCourse));
        $this->student->notify(new AdminAnnouncementNotification('Special Notice', 'Platform update'));

        $first = $this->student->notifications()->first();
        $first->markAsRead();

        $response = $this->actingAs($this->student)
            ->get(route('student.notifications.index', ['filter' => 'unread']));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('notifications'));
        $this->assertEquals(1, $response->viewData('unreadCount'));
    }

    public function test_student_can_clear_all_read_notifications(): void
    {
        $this->student->notify(new CourseEnrollmentNotification($this->freeCourse));
        $this->student->notify(new AdminAnnouncementNotification('Welcome', 'Hello student'));

        $first = $this->student->notifications()->first();
        $first->markAsRead();

        $this->assertEquals(2, $this->student->notifications()->count());

        $response = $this->actingAs($this->student)
            ->delete(route('student.notifications.clearRead'));

        $response->assertRedirect();
        $this->assertEquals(1, $this->student->notifications()->count());
        $this->assertEquals(1, $this->student->unreadNotifications()->count());
    }

    public function test_student_receives_completion_and_certificate_notifications_on_course_completion(): void
    {
        // Setup module and lesson
        $module = CourseModule::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Module 1: Strategy',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1.1: Core Offer',
            'slug' => 'lesson-1-1-' . uniqid(),
            'lesson_type' => LessonType::TEXT,
            'content' => 'Core offer architecture.',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
            'is_preview' => false,
        ]);

        // Enroll student
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete lesson
        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->freeCourse, $lesson]));

        // Check notifications: CourseCompletion and CertificateAvailable
        $notifications = $this->student->notifications()->get();
        $types = $notifications->pluck('data.type')->all();

        $this->assertContains('completion', $types);
        $this->assertContains('certificate', $types);
    }

    public function test_student_receives_payment_and_enrollment_notifications_on_free_order(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-' . uniqid(),
            'user_id' => $this->student->id,
            'course_id' => $this->paidCourse->id,
            'amount' => 0.00,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        $this->actingAs($this->student)
            ->post(route('student.courses.checkout.complete-free', $order));

        $types = $this->student->notifications()->get()->pluck('data.type')->all();
        $this->assertContains('payment', $types);
        $this->assertContains('enrollment', $types);
    }
}