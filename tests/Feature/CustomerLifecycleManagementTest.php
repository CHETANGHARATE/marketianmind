<?php

namespace Tests\Feature;

use App\Enums\CustomerLifecycleStage;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lead;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Order;
use App\Models\Payment;
use App\Models\StudentLearningDay;
use App\Models\User;
use App\Services\CustomerLifecycleService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerLifecycleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected CourseCategory $category;
    protected Course $course1;
    protected Course $course2;
    protected CustomerLifecycleService $lifecycleService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lifecycleService = app(CustomerLifecycleService::class);

        $this->admin = User::factory()->create([
            'name' => 'Admin Boss',
            'email' => 'admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
        ]);

        $this->course1 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Growth Hacking Masterclass',
            'slug' => 'growth-hacking-masterclass',
            'short_description' => 'Fast track growth.',
            'price' => 1999.00,
            'access_days' => 365,
            'status' => CourseStatus::PUBLISHED,
        ]);

        $this->course2 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Social Media Blueprint',
            'slug' => 'social-media-blueprint',
            'short_description' => 'Scale social media.',
            'price' => 1499.00,
            'access_days' => 365,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_lead_lifecycle_stage_derivation_from_unregistered_lead(): void
    {
        $leadNew = Lead::create([
            'name' => 'Cold Lead',
            'email' => 'cold@example.com',
            'status' => 'new',
            'lead_score' => 10,
        ]);

        $leadInterested = Lead::create([
            'name' => 'Hot Prospect',
            'email' => 'hot@example.com',
            'status' => 'qualified',
            'lead_score' => 60,
        ]);

        $this->assertEquals(CustomerLifecycleStage::LEAD, $this->lifecycleService->resolveLeadLifecycleStage($leadNew));
        $this->assertEquals(CustomerLifecycleStage::INTERESTED_PROSPECT, $this->lifecycleService->resolveLeadLifecycleStage($leadInterested));
    }

    public function test_registered_user_with_no_purchases_is_interested_prospect(): void
    {
        $student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::INTERESTED_PROSPECT, $stage);
    }

    public function test_first_time_buyer_stage_derivation(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-FTB-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now,
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now,
            'expires_at' => $now->copy()->addDays(365),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now,
            'expires_at' => $now->copy()->addDays(365),
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::FIRST_TIME_BUYER, $stage);
    }

    public function test_active_student_stage_derivation(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        // Course has 2 lessons
        $module = CourseModule::create([
            'course_id' => $this->course1->id,
            'title' => 'Module 1',
            'order' => 1,
            'status' => 'published',
        ]);

        $lesson1 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1-as',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
            'lesson_type' => LessonType::VIDEO,
        ]);

        $lesson2 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 2',
            'slug' => 'lesson-2-as',
            'sort_order' => 2,
            'status' => LessonStatus::PUBLISHED,
            'lesson_type' => LessonType::VIDEO,
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-AS-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(60),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(60),
            'expires_at' => $now->copy()->addDays(200), // more than 30 days left
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(60),
            'expires_at' => $now->copy()->addDays(200),
        ]);

        // Student completed 1 lesson 45 days ago (not in last 30 days -> ACTIVE_STUDENT, not ENGAGED_LEARNER)
        $prog = LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => $now->copy()->subDays(45),
        ]);
        \Illuminate\Support\Facades\DB::table('lesson_progress')
            ->where('id', $prog->id)
            ->update([
                'created_at' => $now->copy()->subDays(45),
                'updated_at' => $now->copy()->subDays(45),
            ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::ACTIVE_STUDENT, $stage);
    }

    public function test_engaged_learner_stage_derivation(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        $module = CourseModule::create([
            'course_id' => $this->course1->id,
            'title' => 'Module 1',
            'order' => 1,
            'status' => 'published',
        ]);

        $lesson1 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1-el',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
            'lesson_type' => LessonType::VIDEO,
        ]);

        $lesson2 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 2',
            'slug' => 'lesson-2-el',
            'sort_order' => 2,
            'status' => LessonStatus::PUBLISHED,
            'lesson_type' => LessonType::VIDEO,
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-EL-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(10),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(10),
            'expires_at' => $now->copy()->addDays(200),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(10),
            'expires_at' => $now->copy()->addDays(200),
        ]);

        // Completed lesson 2 days ago (within 30 days)
        LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => $now->copy()->subDays(2),
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::ENGAGED_LEARNER, $stage);
    }

    public function test_course_completer_stage_derivation(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-CC-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(50),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(50),
            'expires_at' => $now->copy()->addDays(200),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(50),
            'expires_at' => $now->copy()->addDays(200),
        ]);

        Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'certificate_number' => 'CERT-CC-001',
            'student_name' => $student->name,
            'course_title' => $this->course1->title,
            'instructor_name' => 'Marketian Mind Faculty',
            'course_completion_date' => $now->copy()->subDays(5),
            'issued_at' => $now->copy()->subDays(5),
            'verification_token' => 'token-123456',
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::COURSE_COMPLETER, $stage);
    }

    public function test_access_expiring_stage_derivation(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-EXP-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(350),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(350),
            'expires_at' => $now->copy()->addDays(15), // expiring in 15 days
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(350),
            'expires_at' => $now->copy()->addDays(15),
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::ACCESS_EXPIRING, $stage);
    }

    public function test_expired_student_stage_derivation(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-PAST-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(400),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => $now->copy()->subDays(400),
            'expires_at' => $now->copy()->subDays(35), // expired 35 days ago
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(400),
            'expires_at' => $now->copy()->subDays(35),
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::EXPIRED_STUDENT, $stage);
    }

    public function test_returning_customer_stage_derivation(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        // Order 1 (initial)
        Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-RET-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(100),
        ]);

        // Order 2 (second course or renewal)
        Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course2->id,
            'order_number' => 'ORD-RET-002',
            'amount' => 149900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(10),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(10),
            'expires_at' => $now->copy()->addDays(355),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(10),
            'expires_at' => $now->copy()->addDays(355),
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::RETURNING_CUSTOMER, $stage);
    }

    public function test_multiple_courses_independent_access_lifecycle(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        // Course 1 is expired
        $order1 = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-MULT-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(400),
        ]);

        $enrollment1 = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => $now->copy()->subDays(400),
            'starts_at' => $now->copy()->subDays(400),
            'expires_at' => $now->copy()->subDays(35),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment1->id,
            'order_id' => $order1->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(400),
            'expires_at' => $now->copy()->subDays(35),
        ]);

        // Course 2 is active
        $order2 = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course2->id,
            'order_number' => 'ORD-MULT-002',
            'amount' => 149900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(20),
        ]);

        $enrollment2 = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(20),
            'starts_at' => $now->copy()->subDays(20),
            'expires_at' => $now->copy()->addDays(345),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment2->id,
            'order_id' => $order2->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(20),
            'expires_at' => $now->copy()->addDays(345),
        ]);

        // Course-specific stages
        $stageCourse1 = $this->lifecycleService->resolveCourseLifecycleStage($student, $this->course1);
        $stageCourse2 = $this->lifecycleService->resolveCourseLifecycleStage($student, $this->course2);

        $this->assertEquals(CustomerLifecycleStage::EXPIRED_STUDENT, $stageCourse1);
        $this->assertEquals(CustomerLifecycleStage::ACTIVE_STUDENT, $stageCourse2);

        // Overall student lifecycle: has 2 paid orders -> RETURNING_CUSTOMER (NEVER expired!)
        $overallStage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::RETURNING_CUSTOMER, $overallStage);
        $this->assertNotEquals(CustomerLifecycleStage::EXPIRED_STUDENT, $overallStage);
    }

    public function test_checkout_attempt_without_payment_does_not_make_user_buyer(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);

        // Pending and Failed orders
        Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-FAIL-001',
            'amount' => 199900,
            'status' => OrderStatus::FAILED,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
        ]);

        Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course2->id,
            'order_number' => 'ORD-PEND-001',
            'amount' => 149900,
            'status' => OrderStatus::PENDING,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
        ]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::INTERESTED_PROSPECT, $stage);
    }

    public function test_early_renewal_stacking_preserves_active_access_and_sets_returning_customer(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        // Initial purchase
        $initialOrder = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-INIT-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(340),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(340),
            'expires_at' => $now->copy()->addDays(25), // 25 days remaining
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $initialOrder->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(340),
            'expires_at' => $now->copy()->addDays(25),
        ]);

        // Student renews early (order type 'renewal')
        $renewalOrder = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-REN-001',
            'amount' => 149900,
            'status' => OrderStatus::PAID,
            'order_type' => 'renewal',
            'payment_method' => 'razorpay',
            'paid_at' => $now,
        ]);

        // Stacked access period: starts when previous expires
        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $renewalOrder->id,
            'period_type' => 'renewal',
            'starts_at' => $now->copy()->addDays(25),
            'expires_at' => $now->copy()->addDays(25 + 365),
        ]);

        // Update enrollment expires_at to stacked date
        $enrollment->update(['expires_at' => $now->copy()->addDays(25 + 365)]);

        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::RETURNING_CUSTOMER, $stage);
    }

    public function test_access_expiry_preserves_historical_progress_and_certificate(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);
        $now = Carbon::now();

        $module = CourseModule::create([
            'course_id' => $this->course1->id,
            'title' => 'Module 1',
            'order' => 1,
            'status' => 'published',
        ]);

        $lesson = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1',
            'slug' => 'lesson-1-exp',
            'sort_order' => 1,
            'status' => LessonStatus::PUBLISHED,
            'lesson_type' => LessonType::VIDEO,
        ]);

        $progress = LessonProgress::create([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'completed_at' => $now->copy()->subDays(400),
        ]);

        // Enrollment expired
        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => $now->copy()->subDays(500),
            'expires_at' => $now->copy()->subDays(135),
            'access_type' => 'period',
        ]);

        $certificate = Certificate::create([
            'enrollment_id' => $enrollment->id,
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'certificate_number' => 'CERT-EXP-001',
            'student_name' => $student->name,
            'course_title' => $this->course1->title,
            'instructor_name' => 'Marketian Mind Faculty',
            'course_completion_date' => $now->copy()->subDays(400),
            'issued_at' => $now->copy()->subDays(400),
            'verification_token' => 'token-preserved-999',
        ]);

        // Verify records remain preserved and uncorrupted
        $this->assertDatabaseHas('lesson_progress', [
            'id' => $progress->id,
            'completed' => 1,
        ]);

        $this->assertDatabaseHas('certificates', [
            'id' => $certificate->id,
            'certificate_number' => 'CERT-EXP-001',
        ]);

        // Lifecycle stage is Course Completer because certificate exists!
        $stage = $this->lifecycleService->resolveLifecycleStage($student);
        $this->assertEquals(CustomerLifecycleStage::COURSE_COMPLETER, $stage);
    }

    public function test_admin_student_directory_lifecycle_filtering(): void
    {
        $now = Carbon::now();

        // Student 1: Interested Prospect
        $prospect = User::factory()->create([
            'name' => 'Prospect Peter',
            'role' => UserRole::STUDENT,
        ]);

        // Student 2: First Time Buyer
        $buyer = User::factory()->create([
            'name' => 'Buyer Brenda',
            'role' => UserRole::STUDENT,
        ]);

        $order = Order::create([
            'user_id' => $buyer->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-FILT-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now,
        ]);

        $enr = Enrollment::create([
            'user_id' => $buyer->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now,
            'expires_at' => $now->copy()->addDays(365),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enr->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now,
            'expires_at' => $now->copy()->addDays(365),
        ]);

        // Filter by first_time_buyer
        $response = $this->actingAs($this->admin)->get(route('admin.students.index', [
            'lifecycle_stage' => 'first_time_buyer',
        ]));

        $response->assertOk();
        $response->assertSee('Buyer Brenda');
        $response->assertDontSee('Prospect Peter');

        // Filter by interested_prospect
        $responseProspect = $this->actingAs($this->admin)->get(route('admin.students.index', [
            'lifecycle_stage' => 'interested_prospect',
        ]));

        $responseProspect->assertOk();
        $responseProspect->assertSee('Prospect Peter');
        $responseProspect->assertDontSee('Buyer Brenda');
    }

    public function test_admin_student_show_view_renders_lifecycle_inspector_and_timeline(): void
    {
        $now = Carbon::now();
        $student = User::factory()->create([
            'name' => 'Charlie Lifecycle',
            'email' => 'charlie@lifecycle.com',
            'role' => UserRole::STUDENT,
            'email_verified_at' => $now->copy()->subDays(10),
        ]);

        $order = Order::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-INSP-001',
            'amount' => 199900,
            'status' => OrderStatus::PAID,
            'order_type' => 'initial',
            'payment_method' => 'razorpay',
            'paid_at' => $now->copy()->subDays(5),
        ]);

        $enrollment = Enrollment::create([
            'user_id' => $student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => $now->copy()->subDays(5),
            'expires_at' => $now->copy()->addDays(360),
            'access_type' => 'period',
        ]);

        CourseAccessPeriod::create([
            'enrollment_id' => $enrollment->id,
            'order_id' => $order->id,
            'period_type' => 'initial',
            'starts_at' => $now->copy()->subDays(5),
            'expires_at' => $now->copy()->addDays(360),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.show', $student));

        $response->assertOk();
        $response->assertSee('Customer Lifecycle Status');
        $response->assertSee('First-Time Buyer');
        $response->assertSee('Consent & Channels', false);
        $response->assertSee('Customer Lifecycle Audit Timeline');
        $response->assertSee('ORD-INSP-001');
    }

    public function test_non_admin_cannot_access_student_lifecycle_admin_routes(): void
    {
        $student = User::factory()->create(['role' => UserRole::STUDENT]);

        $responseIndex = $this->actingAs($student)->get(route('admin.students.index'));
        $this->assertTrue(in_array($responseIndex->status(), [302, 403]));

        $responseShow = $this->actingAs($student)->get(route('admin.students.show', $student));
        $this->assertTrue(in_array($responseShow->status(), [302, 403]));
    }
}
