<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Mail\CertificateIssuedMail;
use App\Mail\CourseCompletionMail;
use App\Mail\CourseEnrollmentMail;
use App\Mail\OrderConfirmationMail;
use App\Mail\TestMail;
use App\Mail\WelcomeStudentMail;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Order;
use App\Models\User;
use App\Services\TransactionalMailService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TransactionalMailTest extends TestCase
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
            'title' => 'Email Marketing Playbook',
            'slug' => 'email-marketing-playbook-' . uniqid(),
            'short_description' => 'Learn email marketing systems.',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->paidCourse = Course::create([
            'title' => 'Conversion Rate Mastery',
            'slug' => 'conversion-rate-mastery-' . uniqid(),
            'short_description' => 'Turn visitors into paying clients.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_student_registration_dispatches_welcome_email(): void
    {
        Mail::fake();

        $response = $this->post(route('register'), [
            'name' => 'Jane Founder',
            'email' => 'jane@business.com',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('student.dashboard'));

        Mail::assertSent(WelcomeStudentMail::class, function ($mail) {
            return $mail->hasTo('jane@business.com') &&
                   $mail->user->name === 'Jane Founder';
        });
    }

    public function test_free_enrollment_dispatches_course_enrollment_email(): void
    {
        Mail::fake();

        $this->actingAs($this->student)
            ->post(route('student.courses.enroll', $this->freeCourse));

        Mail::assertSent(CourseEnrollmentMail::class, function ($mail) {
            return $mail->hasTo($this->student->email) &&
                   $mail->course->id === $this->freeCourse->id;
        });
    }

    public function test_free_order_checkout_dispatches_order_confirmation_email(): void
    {
        Mail::fake();

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

        Mail::assertSent(OrderConfirmationMail::class, function ($mail) use ($order) {
            return $mail->hasTo($this->student->email) &&
                   $mail->order->id === $order->id;
        });
    }

    public function test_course_completion_dispatches_completion_and_certificate_emails(): void
    {
        Mail::fake();

        $module = CourseModule::create([
            'course_id' => $this->freeCourse->id,
            'title' => 'Module 1: Systems',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1.1: Autoresponders',
            'slug' => 'lesson-1-1-' . uniqid(),
            'lesson_type' => LessonType::TEXT,
            'content' => 'Autoresponder sequence guidelines.',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
            'is_preview' => false,
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->freeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($this->student)
            ->post(route('student.courses.lessons.complete', [$this->freeCourse, $lesson]));

        Mail::assertSent(CourseCompletionMail::class, function ($mail) {
            return $mail->hasTo($this->student->email) &&
                   $mail->course->id === $this->freeCourse->id;
        });

        Mail::assertSent(CertificateIssuedMail::class, function ($mail) {
            return $mail->hasTo($this->student->email) &&
                   $mail->certificate->course_id === $this->freeCourse->id;
        });
    }

    public function test_transactional_mail_service_handles_transport_failure_gracefully(): void
    {
        // Mock Mail facade to throw an exception
        Mail::shouldReceive('to')
            ->andThrow(new Exception('Connection timed out on smtp.hostinger.com:465'));

        $service = new TransactionalMailService();

        // Should return false and not throw an uncaught exception
        $result = $service->sendWelcome($this->student);

        $this->assertFalse($result);
    }

    public function test_guest_and_student_cannot_access_admin_mail_settings(): void
    {
        $this->get(route('admin.mail.index'))->assertRedirect(route('login'));

        $this->actingAs($this->student)
            ->get(route('admin.mail.index'))
            ->assertForbidden();
    }

    public function test_admin_can_view_mail_settings(): void
    {
        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.mail.index'));

        $response->assertOk();
        $response->assertSee('Mail &amp; SMTP Configuration', false);
        $response->assertSee('Send Test Email');
    }

    public function test_admin_can_dispatch_test_email_and_it_is_logged_in_audit_logs(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => UserRole::ADMIN]);

        $response = $this->actingAs($admin)->post(route('admin.mail.test'), [
            'email' => 'admin.test@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        Mail::assertSent(TestMail::class, function ($mail) {
            return $mail->hasTo('admin.test@example.com');
        });

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => 'mail.test_sent',
        ]);
    }
}