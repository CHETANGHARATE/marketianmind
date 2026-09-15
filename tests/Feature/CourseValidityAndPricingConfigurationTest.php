<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Bundle;
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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseValidityAndPricingConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected CourseCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'is_active' => true,
        ]);
    }

    public function test_courses_table_contains_access_validity_days_and_defaults_to_365(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Default Validity Course',
            'slug' => 'default-validity-course',
            'short_description' => 'Test course short description.',
            'price' => 1999.00,
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'access_validity_days' => 365,
        ]);

        $this->assertSame(365, $course->access_validity_days);
        $this->assertSame(365, $course->getAccessValidityDays());
    }

    public function test_course_model_casts_access_validity_days_to_integer(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Cast Test Course',
            'slug' => 'cast-test-course',
            'short_description' => 'Test cast description.',
            'price' => 2999.00,
            'status' => 'published',
            'access_validity_days' => '180',
        ]);

        $this->assertIsInt($course->access_validity_days);
        $this->assertSame(180, $course->access_validity_days);
        $this->assertSame(180, $course->getAccessValidityDays());
    }

    public function test_custom_access_validity_can_be_stored(): void
    {
        $course30 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => '30 Day Sprint',
            'slug' => '30-day-sprint',
            'short_description' => '30-day intensive access.',
            'price' => 999.00,
            'status' => 'published',
            'access_validity_days' => 30,
        ]);

        $course730 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => '2 Year Executive Mastery',
            'slug' => '2-year-executive-mastery',
            'short_description' => '730-day multi-year access.',
            'price' => 9999.00,
            'status' => 'published',
            'access_validity_days' => 730,
        ]);

        $this->assertSame(30, $course30->getAccessValidityDays());
        $this->assertSame(730, $course730->getAccessValidityDays());
    }

    public function test_admin_can_create_course_with_custom_access_validity(): void
    {
        $payload = [
            'title' => 'Advanced Google Ads 2026',
            'slug' => 'advanced-google-ads-2026',
            'short_description' => 'Master PPC advertising campaigns.',
            'description' => 'Full syllabus and strategy guide.',
            'course_category_id' => $this->category->id,
            'instructor_name' => 'Chetan Gharate',
            'is_free' => '0',
            'price' => '3499.00',
            'discount_price' => '2499.00',
            'status' => 'published',
            'featured' => '0',
            'estimated_duration' => '8 hours',
            'access_validity_days' => 180,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('courses', [
            'slug' => 'advanced-google-ads-2026',
            'access_validity_days' => 180,
            'price' => 3499.00,
        ]);

        $createdCourse = Course::where('slug', 'advanced-google-ads-2026')->firstOrFail();
        $this->assertSame(180, $createdCourse->getAccessValidityDays());
    }

    public function test_admin_can_update_course_access_validity(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Email Marketing Pro',
            'slug' => 'email-marketing-pro',
            'short_description' => 'High conversion cold email workflows.',
            'price' => 1499.00,
            'status' => 'published',
            'access_validity_days' => 365,
        ]);

        $payload = [
            'title' => 'Email Marketing Pro Updated',
            'slug' => 'email-marketing-pro',
            'short_description' => 'High conversion cold email workflows.',
            'course_category_id' => $this->category->id,
            'is_free' => '0',
            'price' => '1499.00',
            'status' => 'published',
            'access_validity_days' => 730,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.courses.update', $course), $payload);

        $response->assertRedirect(route('admin.courses.index'));
        $response->assertSessionHas('success');

        $course->refresh();
        $this->assertSame(730, $course->access_validity_days);
        $this->assertSame(730, $course->getAccessValidityDays());
    }

    public function test_admin_course_creation_defaults_to_365_when_omitted(): void
    {
        $payload = [
            'title' => 'Growth Hacking Fundamentals',
            'slug' => 'growth-hacking-fundamentals',
            'short_description' => 'Viral marketing frameworks.',
            'course_category_id' => $this->category->id,
            'is_free' => '0',
            'price' => '1999.00',
            'status' => 'published',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.index'));

        $this->assertDatabaseHas('courses', [
            'slug' => 'growth-hacking-fundamentals',
            'access_validity_days' => 365,
        ]);
    }

    public function test_invalid_zero_access_validity_is_rejected(): void
    {
        $payload = [
            'title' => 'Zero Validity Test',
            'short_description' => 'Testing zero validity.',
            'course_category_id' => $this->category->id,
            'is_free' => '0',
            'price' => '1999.00',
            'status' => 'published',
            'access_validity_days' => 0,
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.courses.create'))
            ->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.create'));
        $response->assertSessionHasErrors(['access_validity_days']);
    }

    public function test_invalid_negative_access_validity_is_rejected(): void
    {
        $payload = [
            'title' => 'Negative Validity Test',
            'short_description' => 'Testing negative validity.',
            'course_category_id' => $this->category->id,
            'is_free' => '0',
            'price' => '1999.00',
            'status' => 'published',
            'access_validity_days' => -30,
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.courses.create'))
            ->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.create'));
        $response->assertSessionHasErrors(['access_validity_days']);
    }

    public function test_invalid_non_integer_access_validity_is_rejected(): void
    {
        $payload = [
            'title' => 'Non-Integer Validity Test',
            'short_description' => 'Testing string validity.',
            'course_category_id' => $this->category->id,
            'is_free' => '0',
            'price' => '1999.00',
            'status' => 'published',
            'access_validity_days' => 'one_year',
        ];

        $response = $this->actingAs($this->admin)
            ->from(route('admin.courses.create'))
            ->post(route('admin.courses.store'), $payload);

        $response->assertRedirect(route('admin.courses.create'));
        $response->assertSessionHasErrors(['access_validity_days']);
    }

    public function test_students_cannot_modify_course_validity(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Secure Course',
            'slug' => 'secure-course',
            'short_description' => 'Security test course.',
            'price' => 1999.00,
            'status' => 'published',
            'access_validity_days' => 365,
        ]);

        $response = $this->actingAs($this->student)
            ->put(route('admin.courses.update', $course), [
                'title' => 'Hacked Course',
                'short_description' => 'Hacked description.',
                'price' => '100.00',
                'status' => 'published',
                'access_validity_days' => 9999,
            ]);

        $response->assertStatus(403);

        $course->refresh();
        $this->assertSame(365, $course->access_validity_days);
    }

    public function test_guests_cannot_modify_course_validity(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Guest Secure Course',
            'slug' => 'guest-secure-course',
            'short_description' => 'Guest security test.',
            'price' => 1999.00,
            'status' => 'published',
            'access_validity_days' => 365,
        ]);

        $response = $this->put(route('admin.courses.update', $course), [
            'title' => 'Guest Modified Course',
            'short_description' => 'Guest modified description.',
            'price' => '100.00',
            'status' => 'published',
            'access_validity_days' => 9999,
        ]);

        $response->assertRedirect(route('login'));

        $course->refresh();
        $this->assertSame(365, $course->access_validity_days);
    }

    public function test_course_pricing_methods_remain_intact(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Pricing Integrity Course',
            'slug' => 'pricing-integrity-course',
            'short_description' => 'Verifying price calculations.',
            'price' => 4999.00,
            'discount_price' => 2999.00,
            'is_free' => false,
            'status' => 'published',
            'access_validity_days' => 365,
        ]);

        $this->assertSame(2999.00, $course->effectivePrice());
        $this->assertSame(299900, $course->effectivePriceInPaise());
        $this->assertTrue($course->hasDiscount());
        $this->assertSame('₹2,999.00', $course->formattedPrice());
    }

    public function test_phase_10_1_access_period_architecture_remains_intact_with_course_validity(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Access Period Linked Course',
            'slug' => 'access-period-linked-course',
            'short_description' => 'Testing relationship with CourseAccessPeriod.',
            'price' => 1999.00,
            'status' => 'published',
            'access_validity_days' => 365,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'status' => EnrollmentStatus::ACTIVE->value,
            'starts_at' => now(),
            'expires_at' => now()->addDays($course->getAccessValidityDays()),
        ]);

        $period = CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => now(),
            'expires_at' => now()->addDays($course->getAccessValidityDays()),
        ]);

        $this->assertTrue($course->accessPeriods->contains($period));
        $this->assertSame(365, $course->getAccessValidityDays());
        $this->assertFalse($enrollment->isExpired());
    }

    public function test_audit_logger_tracks_access_validity_days_on_course_update(): void
    {
        $course = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Audit Track Course',
            'slug' => 'audit-track-course',
            'short_description' => 'Testing audit log capture.',
            'price' => 1999.00,
            'status' => 'published',
            'access_validity_days' => 365,
        ]);

        $this->actingAs($this->admin)->put(route('admin.courses.update', $course), [
            'title' => 'Audit Track Course',
            'short_description' => 'Testing audit log capture.',
            'course_category_id' => $this->category->id,
            'is_free' => '0',
            'price' => '1999.00',
            'status' => 'published',
            'access_validity_days' => 180,
        ]);

        $latestLog = AuditLog::where('auditable_type', 'Course')
            ->where('auditable_id', $course->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();

        $this->assertNotNull($latestLog);
        $this->assertSame(365, (int) $latestLog->old_values['access_validity_days']);
        $this->assertSame(180, (int) $latestLog->new_values['access_validity_days']);
    }
}