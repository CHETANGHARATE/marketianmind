<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CourseAccessPeriodFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $course;
    protected CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'is_active' => true,
        ]);

        $this->course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'SEO Mastery 2026',
            'slug' => 'seo-mastery-2026',
            'short_description' => 'Comprehensive SEO training program.',
            'description' => 'Comprehensive SEO training program in detail.',
            'price' => 4999.00,
            'status' => 'published',
            'is_free' => false,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);
    }

    public function test_enrollments_table_accepts_starts_at_and_expires_at(): void
    {
        $startsAt = Carbon::parse('2026-09-14 10:00:00');
        $expiresAt = Carbon::parse('2027-09-14 10:00:00');

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'enrolled_at' => now(),
        ]);

        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'starts_at' => $startsAt->toDateTimeString(),
            'expires_at' => $expiresAt->toDateTimeString(),
        ]);
    }

    public function test_enrollment_model_casts_access_dates_correctly(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => '2026-09-14 12:00:00',
            'expires_at' => '2027-09-14 12:00:00',
            'enrolled_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $enrollment->starts_at);
        $this->assertInstanceOf(Carbon::class, $enrollment->expires_at);
        $this->assertSame('2026-09-14 12:00:00', $enrollment->starts_at->toDateTimeString());
        $this->assertSame('2027-09-14 12:00:00', $enrollment->expires_at->toDateTimeString());
    }

    public function test_enrollment_status_enum_contains_expired_and_preserves_existing_values(): void
    {
        $this->assertSame('expired', EnrollmentStatus::EXPIRED->value);
        $this->assertSame('Expired', EnrollmentStatus::EXPIRED->label());

        $this->assertSame('active', EnrollmentStatus::ACTIVE->value);
        $this->assertSame('completed', EnrollmentStatus::COMPLETED->value);
        $this->assertSame('cancelled', EnrollmentStatus::CANCELLED->value);

        $values = EnrollmentStatus::values();
        $this->assertContains('active', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('cancelled', $values);
        $this->assertContains('expired', $values);
    }

    public function test_course_access_period_can_be_created_and_persisted(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertDatabaseHas('course_access_periods', [
            'id' => $period->id,
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'order_id' => null,
        ]);
        $this->assertTrue($period->isInitial());
        $this->assertFalse($period->isRenewal());
        $this->assertFalse($period->isAdminGrant());
    }

    public function test_course_access_period_belongs_to_enrollment(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertInstanceOf(Enrollment::class, $period->enrollment);
        $this->assertSame($enrollment->id, $period->enrollment->id);
    }

    public function test_course_access_period_belongs_to_order_when_order_id_exists(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'ORD-10101',
            'amount' => 499900,
            'status' => OrderStatus::PAID->value,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertInstanceOf(Order::class, $period->order);
        $this->assertSame($order->id, $period->order->id);
    }

    public function test_enrollment_has_many_access_periods(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertCount(0, $enrollment->accessPeriods);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $enrollment->refresh();
        $this->assertCount(1, $enrollment->accessPeriods);
    }

    public function test_multiple_access_periods_can_belong_to_same_enrollment_representing_multi_year_renewals(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => Carbon::parse('2026-01-01'),
            'expires_at' => Carbon::parse('2028-01-01'),
        ]);

        // Year 1 (initial)
        $period1 = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => Carbon::parse('2026-01-01 00:00:00'),
            'expires_at' => Carbon::parse('2027-01-01 00:00:00'),
        ]);

        // Year 2 (renewal)
        $period2 = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'renewal',
            'starts_at' => Carbon::parse('2027-01-01 00:00:00'),
            'expires_at' => Carbon::parse('2028-01-01 00:00:00'),
        ]);

        // Year 3 (admin grant)
        $period3 = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'admin_grant',
            'starts_at' => Carbon::parse('2028-01-01 00:00:00'),
            'expires_at' => Carbon::parse('2029-01-01 00:00:00'),
        ]);

        $enrollment->refresh();
        $this->assertCount(3, $enrollment->accessPeriods);

        $this->assertTrue($period1->isInitial());
        $this->assertTrue($period2->isRenewal());
        $this->assertTrue($period3->isAdminGrant());
    }

    public function test_unique_user_course_constraint_on_enrollments_remains_intact(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $this->expectException(QueryException::class);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);
    }

    public function test_existing_enrollments_can_exist_with_null_access_dates(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'enrolled_at' => now(),
            'starts_at' => null,
            'expires_at' => null,
        ]);

        $this->assertNull($enrollment->starts_at);
        $this->assertNull($enrollment->expires_at);
        $this->assertFalse($enrollment->hasAccessPeriod());
        // Null expires_at must NEVER be interpreted as expired
        $this->assertFalse($enrollment->isExpired());
    }

    public function test_is_expired_helper_behavior(): void
    {
        // Case 1: Status is explicitly EXPIRED
        $expiredByStatus = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::EXPIRED->value,
            'starts_at' => now()->subYears(2),
            'expires_at' => now()->subYear(),
        ]);
        $this->assertTrue($expiredByStatus->isExpired());

        // Cleanup for next case
        $expiredByStatus->delete();

        // Case 2: Status is ACTIVE but expires_at is past
        $expiredByDate = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => now()->subYear()->subDay(),
            'expires_at' => now()->subDay(),
        ]);
        $this->assertTrue($expiredByDate->isExpired());

        $expiredByDate->delete();

        // Case 3: Status is ACTIVE and expires_at is in the future
        $activeEnrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);
        $this->assertFalse($activeEnrollment->isExpired());
    }

    public function test_access_period_is_active_and_is_expired_helpers(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $activePeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addYear(),
        ]);

        $expiredPeriod = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'starts_at' => now()->subYear()->subDay(),
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($activePeriod->isActive());
        $this->assertFalse($activePeriod->isExpired());

        $this->assertFalse($expiredPeriod->isActive());
        $this->assertTrue($expiredPeriod->isExpired());
    }

    public function test_deleting_enrollment_cascades_to_access_periods(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertDatabaseHas('course_access_periods', ['id' => $period->id]);

        $enrollment->delete();

        $this->assertDatabaseMissing('course_access_periods', ['id' => $period->id]);
    }

    public function test_deleting_order_nullifies_order_id_on_access_period_without_deleting_period(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'ORD-DEL-101',
            'amount' => 499900,
            'status' => OrderStatus::PAID->value,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $order->delete();

        $this->assertDatabaseHas('course_access_periods', [
            'id' => $period->id,
            'order_id' => null,
        ]);
    }

    public function test_course_access_periods_relationship_through_enrollments(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertTrue($this->course->accessPeriods->contains($period));
    }

    public function test_order_access_periods_relationship(): void
    {
        $order = Order::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'order_number' => 'ORD-REL-101',
            'amount' => 499900,
            'status' => OrderStatus::PAID->value,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        $this->assertTrue($order->accessPeriods->contains($period));
    }

    public function test_existing_progress_remains_untouched_and_independent(): void
    {
        $module = CourseModule::create([
            'course_id' => $this->course->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1',
            'status' => 'published',
            'sort_order' => 1,
            'duration' => 600,
            'is_preview' => false,
        ]);

        $progress = LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
        ]);

        // Verify progress is completely independent and untouched
        $this->assertDatabaseHas('lesson_progress', [
            'id' => $progress->id,
            'user_id' => $this->student->id,
            'lesson_id' => $lesson->id,
            'completed' => 1,
        ]);
        $metrics = $this->course->progressFor($this->student);
        $this->assertSame(1, $metrics['total']);
        $this->assertSame(1, $metrics['completed']);
        $this->assertSame(100, $metrics['percentage']);
        $this->assertTrue($metrics['is_completed']);
    }

    public function test_existing_certificate_records_remain_untouched_and_independent(): void
    {
        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::COMPLETED->value,
            'completed_at' => now(),
        ]);

        $certificate = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'MM-2026-TEST01',
            'course_title' => $this->course->title,
            'student_name' => $this->student->name,
            'course_completion_date' => now(),
            'issued_at' => now(),
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'starts_at' => now()->subYear(),
            'expires_at' => now(),
        ]);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'certificate_number' => 'MM-2026-TEST01',
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
    }
}
