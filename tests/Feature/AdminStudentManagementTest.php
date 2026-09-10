<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\LessonType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
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

class AdminStudentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $studentA;
    protected User $studentB;
    protected CourseCategory $category;
    protected Course $course1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'name' => 'Admin Boss',
            'email' => 'admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $this->studentA = User::factory()->create([
            'name' => 'Alice Founder',
            'email' => 'alice@startup.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->studentB = User::factory()->create([
            'name' => 'Bob Merchant',
            'email' => 'bob@ecommerce.com',
            'role' => UserRole::STUDENT,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
        ]);

        $this->course1 = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Growth Hacking 101',
            'slug' => 'growth-hacking-101',
            'short_description' => 'Fast track business growth strategies.',
            'price' => 1999.00,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    /**
     * Test guest cannot access student index and is redirected to login.
     */
    public function test_guest_cannot_access_student_list(): void
    {
        $response = $this->get(route('admin.students.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student cannot access admin student list and receives 403 Forbidden.
     */
    public function test_student_cannot_access_admin_student_list(): void
    {
        $response = $this->actingAs($this->studentA)->get(route('admin.students.index'));
        $response->assertForbidden();
    }

    /**
     * Test authorized admin can access the student list.
     */
    public function test_authorized_admin_can_access_student_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.students.index'));

        $response->assertOk();
        $response->assertViewIs('admin.students.index');
        $response->assertSee('Students');
        $response->assertSee('Alice Founder');
        $response->assertSee('Bob Merchant');
    }

    /**
     * Test only student-role users appear and admins are excluded.
     */
    public function test_only_students_appear_in_student_list_and_admins_excluded(): void
    {
        $extraAdmin = User::factory()->create([
            'name' => 'Secondary Admin',
            'email' => 'secondary_admin@marketianmind.com',
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.index'));

        $response->assertOk();
        $students = $response->viewData('students');
        $this->assertCount(2, $students);
        $this->assertFalse($students->contains('id', $this->admin->id));
        $this->assertFalse($students->contains('id', $extraAdmin->id));
        $this->assertTrue($students->contains('id', $this->studentA->id));
        $this->assertTrue($students->contains('id', $this->studentB->id));
    }

    /**
     * Test search by name and email.
     */
    public function test_student_search_filters_by_name_and_email(): void
    {
        // Search by name
        $responseName = $this->actingAs($this->admin)->get(route('admin.students.index', ['search' => 'Alice']));
        $responseName->assertOk();
        $responseName->assertSee('Alice Founder');
        $responseName->assertDontSee('Bob Merchant');

        // Search by email
        $responseEmail = $this->actingAs($this->admin)->get(route('admin.students.index', ['search' => 'ecommerce.com']));
        $responseEmail->assertOk();
        $responseEmail->assertSee('Bob Merchant');
        $responseEmail->assertDontSee('Alice Founder');

        // Search with whitespace and case-insensitive
        $responseTrim = $this->actingAs($this->admin)->get(route('admin.students.index', ['search' => '  aLiCe  ']));
        $responseTrim->assertOk();
        $responseTrim->assertSee('Alice Founder');
        $responseTrim->assertDontSee('Bob Merchant');
    }

    /**
     * Test student search handles empty and special character queries safely.
     */
    public function test_student_search_is_safe_against_sql_injection_and_empty(): void
    {
        // Empty search returns all
        $responseEmpty = $this->actingAs($this->admin)->get(route('admin.students.index', ['search' => '   ']));
        $responseEmpty->assertOk();
        $responseEmpty->assertSee('Alice Founder');
        $responseEmpty->assertSee('Bob Merchant');

        // Special SQL characters do not throw exception
        $responseSql = $this->actingAs($this->admin)->get(route('admin.students.index', ['search' => "' OR '1'='1"]));
        $responseSql->assertOk();
        $responseSql->assertSee('No students found');
    }

    /**
     * Test filters: with_enrollments, without_enrollments, recent.
     */
    public function test_student_filters_work_correctly(): void
    {
        // Alice has an enrollment, Bob has none
        Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Filter: with_enrollments
        $responseWith = $this->actingAs($this->admin)->get(route('admin.students.index', ['filter' => 'with_enrollments']));
        $responseWith->assertOk();
        $responseWith->assertSee('Alice Founder');
        $responseWith->assertDontSee('Bob Merchant');

        // Filter: without_enrollments
        $responseWithout = $this->actingAs($this->admin)->get(route('admin.students.index', ['filter' => 'without_enrollments']));
        $responseWithout->assertOk();
        $responseWithout->assertSee('Bob Merchant');
        $responseWithout->assertDontSee('Alice Founder');

        // Filter: invalid falls back safely to all
        $responseInvalid = $this->actingAs($this->admin)->get(route('admin.students.index', ['filter' => 'hack_filter']));
        $responseInvalid->assertOk();
        $responseInvalid->assertSee('Alice Founder');
        $responseInvalid->assertSee('Bob Merchant');
    }

    /**
     * Test sorting works and invalid sort values are safely sanitized.
     */
    public function test_student_sorting_works_and_sanitizes_input(): void
    {
        // Name A-Z
        $responseAsc = $this->actingAs($this->admin)->get(route('admin.students.index', ['sort' => 'name_asc']));
        $responseAsc->assertOk();
        $studentsAsc = $responseAsc->viewData('students');
        $this->assertEquals('Alice Founder', $studentsAsc->first()->name);

        // Name Z-A
        $responseDesc = $this->actingAs($this->admin)->get(route('admin.students.index', ['sort' => 'name_desc']));
        $responseDesc->assertOk();
        $studentsDesc = $responseDesc->viewData('students');
        $this->assertEquals('Bob Merchant', $studentsDesc->first()->name);

        // Invalid sort string does not crash and defaults safely to newest
        $responseHack = $this->actingAs($this->admin)->get(route('admin.students.index', ['sort' => '; DROP TABLE users; --']));
        $responseHack->assertOk();
    }

    /**
     * Test pagination and query string persistence.
     */
    public function test_pagination_works_and_persists_query_strings(): void
    {
        // Create 20 extra students
        User::factory()->count(20)->create(['role' => UserRole::STUDENT]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.index', ['sort' => 'name_asc', 'search' => 'a']));
        $response->assertOk();
        $students = $response->viewData('students');
        $this->assertCount(15, $students);
        $this->assertTrue($students->hasPages());
    }

    /**
     * Test guest cannot access student detail page.
     */
    public function test_guest_cannot_access_student_details(): void
    {
        $response = $this->get(route('admin.students.show', $this->studentA));
        $response->assertRedirect(route('login'));
    }

    /**
     * Test student cannot access admin student detail page (403).
     */
    public function test_student_cannot_access_student_details(): void
    {
        $response = $this->actingAs($this->studentA)->get(route('admin.students.show', $this->studentA));
        $response->assertForbidden();

        $responseOther = $this->actingAs($this->studentA)->get(route('admin.students.show', $this->studentB));
        $responseOther->assertForbidden();
    }

    /**
     * Test authorized admin can access student detail page.
     */
    public function test_admin_can_view_student_details(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.students.show', $this->studentA));

        $response->assertOk();
        $response->assertViewIs('admin.students.show');
        $response->assertSee('Alice Founder');
        $response->assertSee('alice@startup.com');
        $response->assertSee('Total Enrollments');
        $response->assertSee('Lifetime Investment');
    }

    /**
     * Test accessing a non-student (admin) via admin.students.show returns 404.
     */
    public function test_accessing_non_student_returns_404(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.students.show', $this->admin));
        $response->assertNotFound();
    }

    /**
     * Test student details accurately calculates enrollment progress, orders, revenue, and certificates.
     */
    public function test_student_details_calculates_all_metrics_and_relationships_accurately(): void
    {
        // 1. Create Module and 2 Lessons for course1
        $module = CourseModule::create([
            'course_id' => $this->course1->id,
            'title' => 'Module 1',
            'sort_order' => 1,
        ]);

        $lesson1 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 1 Introduction',
            'slug' => 'lesson-1-intro',
            'type' => LessonType::VIDEO,
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 1,
        ]);

        $lesson2 = Lesson::create([
            'course_module_id' => $module->id,
            'title' => 'Lesson 2 Advanced Tactics',
            'slug' => 'lesson-2-advanced',
            'type' => LessonType::TEXT,
            'status' => LessonStatus::PUBLISHED,
            'sort_order' => 2,
        ]);

        // 2. Enroll Alice and complete Lesson 1 (50% progress)
        $enrollment = Enrollment::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now()->subDays(3),
        ]);

        LessonProgress::create([
            'user_id' => $this->studentA->id,
            'lesson_id' => $lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // 3. Paid order: ₹1,999.00 = 199900 paise
        $orderPaid = Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-ALICE-1',
            'amount' => 199900,
            'currency' => 'INR',
            'status' => OrderStatus::PAID,
            'paid_at' => now(),
        ]);

        // 4. Pending order: ₹5,000.00 = 500000 paise (MUST NOT be counted in revenue)
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-ALICE-2',
            'amount' => 500000,
            'currency' => 'INR',
            'status' => OrderStatus::PENDING,
        ]);

        // 5. Failed order: ₹2,000.00 (MUST NOT be counted in revenue)
        Order::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'order_number' => 'ORD-ALICE-3',
            'amount' => 200000,
            'currency' => 'INR',
            'status' => OrderStatus::FAILED,
        ]);

        // 6. Issued Certificate
        Certificate::create([
            'user_id' => $this->studentA->id,
            'course_id' => $this->course1->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'MM-CERT-ALICE-01',
            'course_title' => $this->course1->title,
            'student_name' => $this->studentA->name,
            'instructor_name' => 'Marketian Mind Faculty',
            'course_completion_date' => now(),
            'issued_at' => now(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.show', $this->studentA));

        $response->assertOk();
        $stats = $response->viewData('stats');
        $this->assertEquals(1, $stats['total_enrollments']);
        $this->assertEquals(1, $stats['in_progress_courses']);
        $this->assertEquals(0, $stats['completed_courses']);
        $this->assertEquals(1, $stats['paid_orders']);
        $this->assertEquals(1999.00, $stats['total_paid_revenue']);
        $this->assertEquals('₹1,999.00', $stats['formatted_paid_revenue']);
        $this->assertEquals(1, $stats['certificates_count']);

        // Check view displays
        $response->assertSee('₹1,999.00');
        $response->assertSee('ORD-ALICE-1');
        $response->assertSee('ORD-ALICE-2');
        $response->assertSee('MM-CERT-ALICE-01');
        $response->assertSee('50%');
        $response->assertSee('1/2 lessons');

        // Verify password hash or sensitive token is NEVER displayed in response
        $response->assertDontSee($this->studentA->password);
    }

    /**
     * Test empty student detail renders cleanly when student has no activity.
     */
    public function test_student_details_renders_empty_activity_cleanly(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.students.show', $this->studentB));

        $response->assertOk();
        $response->assertSee('₹0.00');
        $response->assertSee('No course enrollments found');
        $response->assertSee('No order records found');
        $response->assertSee('No certificates earned yet');
    }

    /**
     * Test existing admin dashboard and orders continue to function.
     */
    public function test_existing_admin_dashboard_and_orders_continue_to_function(): void
    {
        $dashResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $dashResponse->assertOk();

        $ordersResponse = $this->actingAs($this->admin)->get(route('admin.orders.index'));
        $ordersResponse->assertOk();
    }
}