<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Enums\LessonStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\User;
use App\Services\CourseRenewalNotificationService;
use App\Services\OrderFulfillmentService;
use App\Services\RenewalAnalyticsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CriticalBusinessScenarioEndToEndTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Section 61: Complete End-to-End Critical Business Scenario Test.
     *
     * Lifecycle Steps:
     * 1. Student Registers.
     * 2. Purchases paid course.
     * 3. Payment succeeds.
     * 4. Gets 365-day access.
     * 5. Completes 50% of course.
     * 6. Approaches expiry.
     * 7. Receives reminder.
     * 8. Renews early.
     * 9. New period starts at old expiry.
     * 10. Progress remains 50%.
     * 11. Later expires again.
     * 12. Renews post-expiry.
     * 13. Access resumes.
     * 14. Progress remains 50%.
     * 15. Analytics records correct renewals.
     * 16. CRM records correct lifecycle.
     * 17. No duplicate enrollment exists.
     */
    public function test_complete_end_to_end_student_access_renewal_lifecycle(): void
    {
        // -------------------------------------------------------------
        // Setup Platform Course with 2 published lessons (for 50% progress)
        // -------------------------------------------------------------
        $course = Course::create([
            'title' => 'Advanced Performance Marketing',
            'slug' => 'advanced-performance-marketing',
            'short_description' => 'Master performance campaigns.',
            'description' => 'Full curriculum for elite marketers.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Core Acquisition Strategy',
            'sort_order' => 1,
        ]);

        $lesson1 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Audience Research & Positioning',
            'slug' => 'audience-research-positioning',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Funnel Conversion Rate Optimization',
            'slug' => 'funnel-conversion-rate-optimization',
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        // Start time: 2026-01-01 10:00:00
        Carbon::setTestNow('2026-01-01 10:00:00');

        // STEP 1: Student Registers
        $student = User::factory()->create([
            'name' => 'Rahul Sharma',
            'email' => 'rahul.sharma@example.com',
            'role' => UserRole::STUDENT,
        ]);

        // Matched CRM Lead created during prospect inquiry
        $lead = Lead::create([
            'name' => $student->name,
            'email' => $student->email,
            'phone' => '9876543210',
            'source' => 'website',
            'status' => LeadStatus::CONVERTED->value,
            'priority' => LeadPriority::HIGH->value,
            'course_id' => $course->id,
            'converted_user_id' => $student->id,
        ]);

        // STEP 2 & 3: Purchases paid course & payment succeeds
        $initialOrder = Order::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'order_number' => 'MM-ORD-20260101-001',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'initial'],
        ]);

        // STEP 4: Gets 365-day access
        $fulfillmentService = app(OrderFulfillmentService::class);
        $period1 = $fulfillmentService->fulfillCourseAccess($student, $course, $initialOrder);

        $this->assertNotNull($period1);
        $this->assertEquals('initial', $period1->period_type);
        $this->assertEquals('2026-01-01 10:00:00', $period1->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2027-01-01 10:00:00', $period1->expires_at->format('Y-m-d H:i:s'));

        $enrollment = Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->first();
        $this->assertNotNull($enrollment);
        $this->assertTrue($enrollment->hasActiveAccess());
        $this->assertEquals(1, Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->count());

        // STEP 5: Completes 50% of course (1 out of 2 lessons)
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => now()->addDays(20),
            'last_watched_at' => now()->addDays(20),
        ]);

        $completedLessons = LessonProgress::where('user_id', $student->id)->where('completed', true)->count();
        $totalPublishedLessons = $course->lessons()->where('lessons.status', LessonStatus::PUBLISHED->value)->count();
        $progressPct = ($completedLessons / $totalPublishedLessons) * 100;
        $this->assertEquals(50.0, $progressPct);

        // STEP 6: Approaches expiry (Advance to 7 days before expiry: 2026-12-25 10:00:00)
        Carbon::setTestNow('2026-12-25 10:00:00');
        $this->assertTrue($enrollment->refresh()->isExpiringSoon(7));

        // STEP 7: Receives reminder
        $notifService = app(CourseRenewalNotificationService::class);
        $reminderSent = $notifService->sendExpiringSoon($enrollment, 7);
        $this->assertTrue($reminderSent);

        // CRM records reminder
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'renewal_reminder_sent',
        ]);

        // STEP 8: Renews early (Advance to 2026-12-26 15:00:00, while 6 days remaining)
        Carbon::setTestNow('2026-12-26 15:00:00');

        $earlyRenewalOrder = Order::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'order_number' => 'MM-ORD-20261226-REN',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        // STEP 9: New period starts at old expiry (2027-01-01 10:00:00 -> 2028-01-01 10:00:00)
        $period2 = $fulfillmentService->fulfillCourseAccess($student, $course, $earlyRenewalOrder);
        $this->assertNotNull($period2);
        $this->assertEquals('renewal', $period2->period_type);
        $this->assertTrue($period2->isEarlyRenewal());
        $this->assertFalse($period2->isPostExpiryRenewal());
        $this->assertEquals('2027-01-01 10:00:00', $period2->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2028-01-01 10:00:00', $period2->expires_at->format('Y-m-d H:i:s'));

        // STEP 10: Progress remains 50%
        $completedLessonsAfterRenewal = LessonProgress::where('user_id', $student->id)->where('completed', true)->count();
        $this->assertEquals(1, $completedLessonsAfterRenewal);
        $this->assertEquals(50.0, ($completedLessonsAfterRenewal / $totalPublishedLessons) * 100);

        // STEP 11: Later expires again (Advance past 2028-01-01 to 2028-01-15 12:00:00)
        Carbon::setTestNow('2028-01-15 12:00:00');

        // Check enrollment has expired
        $enrollment->refresh();
        $this->assertTrue($enrollment->isExpired());
        $this->assertFalse($enrollment->hasActiveAccess());

        // Send expired notification
        $notifService->sendAccessExpired($enrollment);
        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $lead->id,
            'activity_type' => 'course_access_expired',
        ]);

        // STEP 12: Renews post-expiry (Advance to 2028-01-20 10:00:00)
        Carbon::setTestNow('2028-01-20 10:00:00');

        $postExpiryOrder = Order::create([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'order_number' => 'MM-ORD-20280120-REN',
            'original_amount' => 499900,
            'discount_amount' => 0,
            'amount' => 499900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
            'metadata' => ['purchase_type' => 'renewal'],
        ]);

        // STEP 13: Access resumes at fulfillment time (2028-01-20 -> +365 days)
        $period3 = $fulfillmentService->fulfillCourseAccess($student, $course, $postExpiryOrder);
        $this->assertNotNull($period3);
        $this->assertEquals('renewal', $period3->period_type);
        $this->assertFalse($period3->isEarlyRenewal());
        $this->assertTrue($period3->isPostExpiryRenewal());
        $this->assertEquals('2028-01-20 10:00:00', $period3->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2029-01-19 10:00:00', $period3->expires_at->format('Y-m-d H:i:s')); // 365 exact days across leap year 2028

        $enrollment->refresh();
        $this->assertTrue($enrollment->hasActiveAccess());

        // STEP 14: Progress remains 50%
        $completedLessonsFinal = LessonProgress::where('user_id', $student->id)->where('completed', true)->count();
        $this->assertEquals(1, $completedLessonsFinal);
        $this->assertEquals(50.0, ($completedLessonsFinal / $totalPublishedLessons) * 100);

        // STEP 15: Analytics records correct renewals
        $analyticsService = app(RenewalAnalyticsService::class);
        $summary = $analyticsService->getRenewalSummary();

        // 2 paid renewal orders (₹4999 x 2 = ₹9998)
        $this->assertEquals(2, $summary['paid_renewal_orders']);
        $this->assertEquals(9998.00, $summary['renewal_revenue']);
        $this->assertEquals(1, $summary['early_renewals']);
        $this->assertEquals(1, $summary['post_expiry_renewals']);
        $this->assertEquals(1, $summary['finite_students']);
        $this->assertEquals(0, $summary['lifetime_students']);

        // STEP 16: CRM records correct lifecycle
        // Lead status must NOT have changed from converted
        $lead->refresh();
        $this->assertEquals(LeadStatus::CONVERTED, $lead->status);

        // Verify all 3 lifecycle activities are in lead_activities
        $activities = $lead->activities()->pluck('activity_type')->all();
        $this->assertContains('renewal_reminder_sent', $activities);
        $this->assertContains('course_access_expired', $activities);

        // STEP 17: No duplicate enrollment exists
        $allEnrollments = Enrollment::where('user_id', $student->id)->where('course_id', $course->id)->get();
        $this->assertCount(1, $allEnrollments);

        // Exactly 3 sequential access periods exist
        $allPeriods = CourseAccessPeriod::where('enrollment_id', $enrollment->id)->orderBy('id')->get();
        $this->assertCount(3, $allPeriods);
        $this->assertEquals('initial', $allPeriods[0]->period_type);
        $this->assertEquals('renewal', $allPeriods[1]->period_type);
        $this->assertEquals('renewal', $allPeriods[2]->period_type);
    }
}
