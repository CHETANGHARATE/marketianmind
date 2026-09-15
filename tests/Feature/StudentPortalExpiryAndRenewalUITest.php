<?php

namespace Tests\Feature;

use App\Enums\CoursePurchaseType;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentPortalExpiryAndRenewalUITest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $course1;
    protected Course $course2;
    protected Course $course3;
    protected Course $lifetimeCourse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course1 = Course::create([
            'title' => 'Digital Marketing Mastery',
            'slug' => 'digital-marketing-mastery',
            'short_description' => 'Master practical digital acquisition funnels.',
            'description' => 'Full practical digital acquisition framework for business owners.',
            'price' => 4999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->course2 = Course::create([
            'title' => 'Social Media Growth Engine',
            'slug' => 'social-media-growth-engine',
            'short_description' => 'Scale social content organically.',
            'description' => 'Organic social content tactics for modern businesses.',
            'price' => 2999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->course3 = Course::create([
            'title' => 'SEO & Content Funnels',
            'slug' => 'seo-and-content-funnels',
            'short_description' => 'Rank high and convert visitors.',
            'description' => 'Search engine optimization strategies for predictable inbound leads.',
            'price' => 3499.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);

        $this->lifetimeCourse = Course::create([
            'title' => 'Legacy Marketing Fundamentals',
            'slug' => 'legacy-marketing-fundamentals',
            'short_description' => 'Timeless marketing principles.',
            'description' => 'Foundational business and marketing strategies.',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'access_validity_days' => 365,
        ]);
    }

    /**
     * Helper to create module and lessons for a course.
     */
    protected function createLessonsForCourse(Course $course, int $count = 3): array
    {
        $module = CourseModule::create([
            'course_id' => $course->id,
            'title' => 'Core Module',
            'slug' => 'core-module-' . $course->id,
            'sort_order' => 1,
        ]);

        $lessons = [];
        for ($i = 1; $i <= $count; $i++) {
            $lessons[] = Lesson::create([
                'course_module_id' => $module->id,
                'title' => "Lesson {$i} of {$course->title}",
                'slug' => "lesson-{$i}-" . $course->id,
                'content' => "Lesson content {$i}",
                'status' => LessonStatus::PUBLISHED,
                'sort_order' => $i,
                'is_preview' => false,
            ]);
        }

        return $lessons;
    }

    public function test_student_dashboard_displays_access_status_summary_pills(): void
    {
        // 1 Active (120 days remaining)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(30),
            'starts_at' => Carbon::now()->subDays(30),
            'expires_at' => Carbon::now()->addDays(120),
        ]);

        // 1 Expiring Soon (15 days remaining)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(350),
            'starts_at' => Carbon::now()->subDays(350),
            'expires_at' => Carbon::now()->addDays(15),
        ]);

        // 1 Expired (expired 10 days ago)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course3->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(375),
            'starts_at' => Carbon::now()->subDays(375),
            'expires_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Access Active');
        $response->assertSee('Expiring Soon');
        $response->assertSee('Access Expired');
    }

    public function test_student_dashboard_continue_learning_card_shows_active_access_badge_and_remaining_days(): void
    {
        $this->createLessonsForCourse($this->course1, 2);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(10),
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->addDays(100),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Access Active');
        $response->assertSee('100 days remaining');
        $response->assertSee(Carbon::now()->addDays(100)->format('d M Y'));
        $response->assertSee('Start Course');
        $response->assertDontSee('Renew Early');
        $response->assertDontSee('Renew Access');
    }

    public function test_student_dashboard_continue_learning_card_shows_expiring_soon_warning_and_renew_early_cta(): void
    {
        $this->createLessonsForCourse($this->course1, 2);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(345),
            'starts_at' => Carbon::now()->subDays(345),
            'expires_at' => Carbon::now()->addDays(20),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Expiring Soon');
        $response->assertSee('20 days remaining');
        $response->assertSee('Renew Early');
        $response->assertSee(route('student.courses.purchase', $this->course1));
    }

    public function test_student_dashboard_continue_learning_card_shows_expired_badge_and_renew_access_cta(): void
    {
        $this->createLessonsForCourse($this->course1, 2);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(400),
            'starts_at' => Carbon::now()->subDays(400),
            'expires_at' => Carbon::now()->subDays(35),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Access Expired');
        $response->assertSee('Renew Access');
        $response->assertSee('Access expired');
        $response->assertSee(route('student.courses.purchase', $this->course1));
    }

    public function test_student_dashboard_lifetime_access_enrollment_shows_lifetime_badge_and_no_countdown(): void
    {
        $this->createLessonsForCourse($this->lifetimeCourse, 2);

        // Lifetime access has starts_at = null and expires_at = null
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->lifetimeCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(500),
            'starts_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Lifetime Access');
        $response->assertDontSee('days remaining');
        $response->assertDontSee('Renew Early');
        $response->assertDontSee('Renew Access');
    }

    public function test_student_dashboard_completed_courses_retain_certificate_link_even_when_access_is_expired(): void
    {
        $lessons = $this->createLessonsForCourse($this->course1, 2);

        $enrollment = Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => Carbon::now()->subDays(400),
            'starts_at' => Carbon::now()->subDays(400),
            'expires_at' => Carbon::now()->subDays(35),
            'completed_at' => Carbon::now()->subDays(50),
        ]);

        foreach ($lessons as $lesson) {
            LessonProgress::create([
                'user_id' => $this->student->id,
                'lesson_id' => $lesson->id,
                'completed' => true,
                'completed_at' => Carbon::now()->subDays(50),
            ]);
        }

        $cert = Certificate::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'enrollment_id' => $enrollment->id,
            'certificate_number' => 'CERT-TEST-12345',
            'course_title' => $this->course1->title,
            'student_name' => $this->student->name,
            'course_completion_date' => Carbon::now()->subDays(50),
            'issued_at' => Carbon::now()->subDays(50),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Completed');
        $response->assertSee('View Certificate');
        $response->assertSee(route('student.certificates.show', $cert));
        $response->assertSee('Renew Access');
    }

    public function test_student_courses_page_displays_filter_tabs_with_accurate_counts(): void
    {
        // 1 Active (100 days)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(10),
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->addDays(100),
        ]);

        // 1 Expiring Soon (15 days)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(350),
            'starts_at' => Carbon::now()->subDays(350),
            'expires_at' => Carbon::now()->addDays(15),
        ]);

        // 1 Expired (-10 days)
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course3->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(375),
            'starts_at' => Carbon::now()->subDays(375),
            'expires_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses'));

        $response->assertStatus(200);
        $response->assertSee('All Courses');
        $response->assertSee('Active');
        $response->assertSee('Expiring Soon');
        $response->assertSee('Expired');
        $response->assertSee('Completed');
        $response->assertSee('My Courses &amp; Access', false);
    }

    public function test_student_courses_page_filtering_by_status_query_parameter(): void
    {
        // Active
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(10),
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->addDays(100),
        ]);

        // Expiring
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(350),
            'starts_at' => Carbon::now()->subDays(350),
            'expires_at' => Carbon::now()->addDays(15),
        ]);

        // Expired
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course3->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(375),
            'starts_at' => Carbon::now()->subDays(375),
            'expires_at' => Carbon::now()->subDays(10),
        ]);

        // Filter: active
        $responseActive = $this->actingAs($this->student)->get(route('student.courses', ['status' => 'active']));
        $responseActive->assertStatus(200);
        $responseActive->assertSee($this->course1->title);
        $responseActive->assertDontSee($this->course2->title);
        $responseActive->assertDontSee($this->course3->title);

        // Filter: expiring
        $responseExpiring = $this->actingAs($this->student)->get(route('student.courses', ['status' => 'expiring']));
        $responseExpiring->assertStatus(200);
        $responseExpiring->assertSee($this->course2->title);
        $responseExpiring->assertDontSee($this->course1->title);
        $responseExpiring->assertDontSee($this->course3->title);

        // Filter: expired
        $responseExpired = $this->actingAs($this->student)->get(route('student.courses', ['status' => 'expired']));
        $responseExpired->assertStatus(200);
        $responseExpired->assertSee($this->course3->title);
        $responseExpired->assertDontSee($this->course1->title);
        $responseExpired->assertDontSee($this->course2->title);
    }

    public function test_student_courses_page_renders_access_strip_and_renewal_cta(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course2->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(350),
            'starts_at' => Carbon::now()->subDays(350),
            'expires_at' => Carbon::now()->addDays(15),
        ]);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course3->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(375),
            'starts_at' => Carbon::now()->subDays(375),
            'expires_at' => Carbon::now()->subDays(10),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses'));

        $response->assertStatus(200);
        $response->assertSee('15 days remaining');
        $response->assertSee('Renew Early');
        $response->assertSee('Access Expired');
        $response->assertSee('Renew Access');
        $response->assertSee('course progress is permanently preserved');
    }

    public function test_public_course_show_page_displays_updated_access_terms(): void
    {
        $response = $this->get(route('courses.show', $this->course1->slug));

        $response->assertStatus(200);
        $response->assertSee('365-day course access');
        $response->assertSee('Manual renewal');
        $response->assertSee('Progress permanently preserved');
        $response->assertDontSee('Lifetime access • All future updates included');
    }

    public function test_public_course_show_page_displays_enrolled_active_state_for_active_student(): void
    {
        $this->createLessonsForCourse($this->course1, 2);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(10),
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->addDays(200),
        ]);

        $response = $this->actingAs($this->student)->get(route('courses.show', $this->course1->slug));

        $response->assertStatus(200);
        $response->assertSee('Continue Learning');
        $response->assertDontSee('Renew Early');
        $response->assertDontSee('Course Access Expired');
    }

    public function test_public_course_show_page_displays_expiring_soon_notice_and_renew_early_cta(): void
    {
        $this->createLessonsForCourse($this->course1, 2);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(345),
            'starts_at' => Carbon::now()->subDays(345),
            'expires_at' => Carbon::now()->addDays(20),
        ]);

        $response = $this->actingAs($this->student)->get(route('courses.show', $this->course1->slug));

        $response->assertStatus(200);
        $response->assertSee('Access Expiring Soon');
        $response->assertSee('20 days remaining');
        $response->assertSee('Renew Early');
        $response->assertSee('Continue Learning');
    }

    public function test_public_course_show_page_displays_expired_notice_and_renew_course_access_cta(): void
    {
        $this->createLessonsForCourse($this->course1, 2);

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(400),
            'starts_at' => Carbon::now()->subDays(400),
            'expires_at' => Carbon::now()->subDays(35),
        ]);

        $response = $this->actingAs($this->student)->get(route('courses.show', $this->course1->slug));

        $response->assertStatus(200);
        $response->assertSee('Course Access Expired');
        $response->assertSee('Renew Course Access');
        $response->assertSee('learning progress and certificate records remain permanently preserved');
        $response->assertDontSee('Continue Learning');
    }

    public function test_renewal_cta_submits_to_existing_purchase_endpoint(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(400),
            'starts_at' => Carbon::now()->subDays(400),
            'expires_at' => Carbon::now()->subDays(35),
        ]);

        $response = $this->actingAs($this->student)->post(route('student.courses.purchase', $this->course1));

        $response->assertSessionHasNoErrors();
        $order = \App\Models\Order::where('user_id', $this->student->id)->where('course_id', $this->course1->id)->latest()->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('student.courses.checkout', $order));
    }

    public function test_expired_student_cannot_access_lesson_and_receives_403(): void
    {
        $lessons = $this->createLessonsForCourse($this->course1, 1);
        $lesson = $lessons[0];

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::EXPIRED,
            'enrolled_at' => Carbon::now()->subDays(400),
            'starts_at' => Carbon::now()->subDays(400),
            'expires_at' => Carbon::now()->subDays(35),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course1, $lesson]));

        $response->assertStatus(403);
    }

    public function test_lesson_player_renders_expiring_soon_banner_when_within_warning_window(): void
    {
        $lessons = $this->createLessonsForCourse($this->course1, 1);
        $lesson = $lessons[0];

        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course1->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => Carbon::now()->subDays(345),
            'starts_at' => Carbon::now()->subDays(345),
            'expires_at' => Carbon::now()->addDays(20),
        ]);

        $response = $this->actingAs($this->student)->get(route('student.courses.lessons.show', [$this->course1, $lesson]));

        $response->assertStatus(200);
        $response->assertSee('Access Expiring Soon');
        $response->assertSee('20 days remaining');
        $response->assertSee('Renew Early');
    }

    public function test_singular_and_plural_remaining_days_formatting(): void
    {
        $enrollmentSingular = new Enrollment([
            'starts_at' => Carbon::now()->subDays(364),
            'expires_at' => Carbon::now()->addDay(),
        ]);

        $this->assertEquals('1 day remaining', $enrollmentSingular->getRemainingDaysText());

        $enrollmentPlural = new Enrollment([
            'starts_at' => Carbon::now()->subDays(360),
            'expires_at' => Carbon::now()->addDays(5),
        ]);

        $this->assertEquals('5 days remaining', $enrollmentPlural->getRemainingDaysText());

        $enrollmentExpired = new Enrollment([
            'starts_at' => Carbon::now()->subDays(370),
            'expires_at' => Carbon::now()->subDay(),
        ]);

        $this->assertEquals('Access expired', $enrollmentExpired->getRemainingDaysText());

        $enrollmentLifetime = new Enrollment([
            'starts_at' => null,
            'expires_at' => null,
        ]);

        $this->assertEquals('Lifetime Access', $enrollmentLifetime->getRemainingDaysText());
    }

    public function test_zero_n_plus_one_queries_on_courses_index_page(): void
    {
        // Create 5 courses and enroll student
        for ($i = 1; $i <= 5; $i++) {
            $c = Course::create([
                'title' => "Batch Course {$i}",
                'slug' => "batch-course-{$i}",
                'short_description' => 'Test course',
                'description' => 'Test description',
                'price' => 1999.00,
                'is_free' => false,
                'status' => CourseStatus::PUBLISHED,
                'access_validity_days' => 365,
            ]);

            Enrollment::create([
                'user_id' => $this->student->id,
                'course_id' => $c->id,
                'status' => EnrollmentStatus::ACTIVE,
                'enrolled_at' => Carbon::now()->subDays(10),
                'starts_at' => Carbon::now()->subDays(10),
                'expires_at' => Carbon::now()->addDays(200),
            ]);
        }

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();

        $response = $this->actingAs($this->student)->get(route('student.courses'));

        $response->assertStatus(200);

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // Check that enrollments table is queried exactly once for user enrollments (no N+1 per course card)
        $enrollmentQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'from "enrollments"')
                || str_contains(strtolower($query['query']), 'from `enrollments`');
        });

        // Exactly 1 query fetches user enrollments with eager loading
        $this->assertLessThanOrEqual(2, count($enrollmentQueries));
    }
}
