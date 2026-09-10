<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\LessonStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseModule;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseDetailTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $admin;
    protected CourseCategory $category;
    protected CourseCategory $otherCategory;
    protected Course $publishedPaidCourse;
    protected Course $publishedFreeCourse;
    protected Course $publishedDiscountCourse;
    protected Course $draftCourse;
    protected Course $archivedCourse;
    protected CourseModule $module1;
    protected CourseModule $module2;
    protected Lesson $lesson1;
    protected Lesson $lesson2;
    protected Lesson $lesson3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->otherStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->category = CourseCategory::create([
            'name' => 'SEO & Organic Growth',
            'slug' => 'seo-organic-growth',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $this->otherCategory = CourseCategory::create([
            'name' => 'Social Media Strategy',
            'slug' => 'social-media-strategy',
            'status' => CategoryStatus::ACTIVE,
        ]);

        // Published Paid Course (no discount)
        $this->publishedPaidCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'SEO Mastery for Small Business Owners',
            'slug' => 'seo-mastery-for-small-business-owners',
            'short_description' => 'A practical framework to rank locally on Google without an agency.',
            'description' => 'Detailed curriculum teaching keyword research, on-page optimization, and local Google Business Profile setup.',
            'price' => 2999.00,
            'discount_price' => null,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'featured' => true,
            'instructor_name' => 'Sarah Content',
            'estimated_duration' => '4 Weeks',
        ]);

        // Published Free Course
        $this->publishedFreeCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Google Business Profile Essentials',
            'slug' => 'google-business-profile-essentials',
            'short_description' => 'Set up and verify your Google map pin for fast local customer discovery.',
            'description' => 'Free crash course on dominating local maps in your city.',
            'price' => 0.00,
            'discount_price' => null,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
            'featured' => false,
            'instructor_name' => 'Local Growth Pro',
            'estimated_duration' => '1 Hour',
        ]);

        // Published Discounted Course
        $this->publishedDiscountCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Advanced Customer Funnel Mastery',
            'slug' => 'advanced-customer-funnel-mastery',
            'short_description' => 'Transform casual website clicks into loyal paying clients.',
            'description' => 'In-depth funnel architecture and conversion rate optimization for founders.',
            'price' => 4999.00,
            'discount_price' => 2499.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'featured' => false,
            'instructor_name' => 'Funnel Architect',
            'estimated_duration' => '6 Weeks',
        ]);

        // Draft Course
        $this->draftCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Unreleased Draft Strategy Playbook',
            'slug' => 'unreleased-draft-strategy-playbook',
            'short_description' => 'Under active construction.',
            'description' => 'Secret roadmap.',
            'price' => 9999.00,
            'is_free' => false,
            'status' => CourseStatus::DRAFT,
        ]);

        // Archived Course
        $this->archivedCourse = Course::create([
            'course_category_id' => $this->category->id,
            'title' => 'Legacy 2021 Marketing Method',
            'slug' => 'legacy-2021-marketing-method',
            'short_description' => 'Deprecated content.',
            'description' => 'Old strategies.',
            'price' => 999.00,
            'is_free' => false,
            'status' => CourseStatus::ARCHIVED,
        ]);

        // Setup Modules & Lessons for SEO course
        $this->module1 = CourseModule::create([
            'course_id' => $this->publishedPaidCourse->id,
            'title' => 'Fundamentals of Local Search',
            'description' => 'How Google indexes and surfaces nearby businesses.',
            'sort_order' => 1,
        ]);

        $this->module2 = CourseModule::create([
            'course_id' => $this->publishedPaidCourse->id,
            'title' => 'On-Page Ranking Optimization',
            'description' => 'Optimizing title tags and local landing pages.',
            'sort_order' => 2,
        ]);

        $this->lesson1 = Lesson::create([
            'course_module_id' => $this->module1->id,
            'title' => 'Understanding Search Intent for Local Buyers',
            'slug' => 'understanding-search-intent',
            'duration' => '12 mins',
            'sort_order' => 1,
            'is_preview' => true,
            'status' => LessonStatus::PUBLISHED,
            'content_type' => 'video',
            'video_url' => 'https://example.com/protected-video-stream-12345',
        ]);

        $this->lesson2 = Lesson::create([
            'course_module_id' => $this->module1->id,
            'title' => 'Competitor Keyword Gap Analysis',
            'slug' => 'competitor-keyword-gap-analysis',
            'duration' => '18 mins',
            'sort_order' => 2,
            'is_preview' => false,
            'status' => LessonStatus::PUBLISHED,
            'content_type' => 'video',
            'video_url' => 'https://example.com/protected-video-stream-67890',
        ]);

        $this->lesson3 = Lesson::create([
            'course_module_id' => $this->module2->id,
            'title' => 'Landing Page Conversion Checklist',
            'slug' => 'landing-page-conversion-checklist',
            'duration' => '15 mins',
            'sort_order' => 1,
            'is_preview' => false,
            'status' => LessonStatus::PUBLISHED,
            'content_type' => 'pdf',
            'pdf_file' => 'pdfs/confidential-checklist.pdf',
        ]);
    }

    /**
     * TEST 1: Guest can view published course.
     */
    public function test_guest_can_view_published_course(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('SEO Mastery for Small Business Owners');
    }

    /**
     * TEST 2: Guest cannot view unpublished draft course (returns 404).
     */
    public function test_guest_cannot_view_unpublished_draft_course(): void
    {
        $response = $this->get('/courses/' . $this->draftCourse->slug);

        $response->assertStatus(404);
    }

    /**
     * TEST 3: Authenticated student can view published course.
     */
    public function test_authenticated_student_can_view_published_course(): void
    {
        $response = $this->actingAs($this->student)->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('SEO Mastery for Small Business Owners');
    }

    /**
     * TEST 4: Course title and details render correctly.
     */
    public function test_course_title_and_details_render_correctly(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('SEO Mastery for Small Business Owners');
        $response->assertSee('Sarah Content');
        $response->assertSee('4 Weeks');
        $response->assertSee('SEO &amp; Organic Growth', false);
        $response->assertSee('About This Course');
        $response->assertSee('Detailed curriculum teaching keyword research');
    }

    /**
     * TEST 5: Standard paid course pricing renders correctly.
     */
    public function test_paid_course_pricing_renders_correctly(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('₹2,999.00');
        $response->assertSee('Paid Program');
    }

    /**
     * TEST 6: Discount pricing renders correctly with savings badge.
     */
    public function test_discount_pricing_renders_correctly_with_savings(): void
    {
        $response = $this->get('/courses/' . $this->publishedDiscountCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('₹2,499.00');
        $response->assertSee('₹4,999.00');
        $response->assertSee('Save ₹2,500');
        $response->assertSee('50% OFF');
    }

    /**
     * TEST 7: Free course shows correct CTA.
     */
    public function test_free_course_shows_correct_cta(): void
    {
        // Guest sees Enroll Free linking to login
        $guestRes = $this->get('/courses/' . $this->publishedFreeCourse->slug);
        $guestRes->assertStatus(200);
        $guestRes->assertSee('Free');
        $guestRes->assertSee('Login to Enroll Free');
        $guestRes->assertSee(route('login'));

        // Authenticated student sees POST enrollment form
        $studentRes = $this->actingAs($this->student)->get('/courses/' . $this->publishedFreeCourse->slug);
        $studentRes->assertStatus(200);
        $studentRes->assertSee('Free');
        $studentRes->assertSee('Enroll for Free');
        $studentRes->assertSee(route('student.courses.enroll', $this->publishedFreeCourse));
    }

    /**
     * TEST 8: Paid course shows Buy Now CTA.
     */
    public function test_paid_course_shows_buy_now_cta(): void
    {
        // Guest sees Buy Now linking to login
        $guestRes = $this->get('/courses/' . $this->publishedPaidCourse->slug);
        $guestRes->assertStatus(200);
        $guestRes->assertSee('Buy Now');
        $guestRes->assertSee(route('login'));

        // Authenticated student sees POST purchase form
        $studentRes = $this->actingAs($this->student)->get('/courses/' . $this->publishedPaidCourse->slug);
        $studentRes->assertStatus(200);
        $studentRes->assertSee('Buy Now');
        $studentRes->assertSee(route('student.courses.purchase', $this->publishedPaidCourse));
    }

    /**
     * TEST 9: Enrolled student sees Continue Learning CTA.
     */
    public function test_enrolled_student_sees_continue_learning_cta(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedPaidCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('Continue Learning');
        $response->assertSee(route('student.courses.show', $this->publishedPaidCourse));
        $response->assertDontSee('Buy Now &rarr;');
    }

    /**
     * TEST 10: Completed student sees Review Course CTA.
     */
    public function test_completed_student_sees_review_course_cta(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedPaidCourse->id,
            'status' => EnrollmentStatus::COMPLETED,
            'enrolled_at' => now()->subWeeks(2),
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('Review Course');
        $response->assertSee(route('student.courses.show', $this->publishedPaidCourse));
    }

    /**
     * TEST 11: Enrolled student sees correct progress bar and percentage.
     */
    public function test_enrolled_student_sees_correct_progress(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedPaidCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Complete 1 of 3 lessons (33%)
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($this->student)->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('Your Learning Progress');
        $response->assertSee('33%');
        $response->assertSee('1 of 3 lessons completed');
    }

    /**
     * TEST 12: Non-enrolled student does not see another student's progress.
     */
    public function test_non_enrolled_student_does_not_see_another_students_progress(): void
    {
        // Enrolled student has completed progress
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedPaidCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);
        LessonProgress::create([
            'user_id' => $this->student->id,
            'lesson_id' => $this->lesson1->id,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // Other student (not enrolled) visits the page
        $response = $this->actingAs($this->otherStudent)->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertDontSee('Your Learning Progress');
        $response->assertDontSee('33%');
        $response->assertSee('Buy Now');
    }

    /**
     * TEST 13: Free enrollment uses existing enrollment functionality.
     */
    public function test_free_enrollment_uses_existing_enrollment_functionality(): void
    {
        $response = $this->actingAs($this->student)
            ->post('/student/courses/' . $this->publishedFreeCourse->id . '/enroll');

        $response->assertRedirect(route('student.courses.show', $this->publishedFreeCourse));

        $this->assertDatabaseHas('enrollments', [
            'user_id' => $this->student->id,
            'course_id' => $this->publishedFreeCourse->id,
            'status' => EnrollmentStatus::ACTIVE->value,
        ]);
    }

    /**
     * TEST 14 & 15: Paid purchase uses existing purchase flow and server-authoritative pricing.
     */
    public function test_paid_purchase_initiates_existing_checkout_with_server_authoritative_price(): void
    {
        // Student initiates purchase POST
        $response = $this->actingAs($this->student)
            ->post('/student/courses/' . $this->publishedPaidCourse->id . '/purchase');

        $response->assertRedirect();
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->student->id,
            'course_id' => $this->publishedPaidCourse->id,
            'amount' => 299900, // strictly 2999 INR in paise
            'currency' => 'INR',
        ]);
    }

    /**
     * TEST 16: Protected lesson video URLs and PDFs are never exposed to non-enrolled visitors.
     */
    public function test_protected_lesson_content_remains_protected(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertDontSee('https://example.com/protected-video-stream-12345');
        $response->assertDontSee('https://example.com/protected-video-stream-67890');
        $response->assertDontSee('pdfs/confidential-checklist.pdf');
    }

    /**
     * TEST 17: Curriculum accordion displays modules and published lessons structure.
     */
    public function test_curriculum_displays_correctly(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('Course Modules Preview');
        $response->assertSee('Fundamentals of Local Search');
        $response->assertSee('On-Page Ranking Optimization');
        $response->assertSee('Understanding Search Intent for Local Buyers');
        $response->assertSee('Competitor Keyword Gap Analysis');
        $response->assertSee('Landing Page Conversion Checklist');
        $response->assertSee('Preview');
    }

    /**
     * TEST 18 & 19: Related courses section excludes current course and includes only published courses.
     */
    public function test_related_courses_exclude_current_course_and_only_include_published_courses(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('Related Courses');
        // Related courses should include the other published courses in the same or other categories
        $response->assertSee('Google Business Profile Essentials');
        // Draft and archived courses must never appear in related courses
        $response->assertDontSee('Unreleased Draft Strategy Playbook');
        $response->assertDontSee('Legacy 2021 Marketing Method');
    }

    /**
     * TEST 20: Breadcrumbs render correct hierarchy and routes.
     */
    public function test_breadcrumbs_render_correctly(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee(route('home'));
        $response->assertSee(route('courses'));
        $response->assertSee(route('courses', ['category' => $this->category->slug]));
        $response->assertSee('SEO Mastery for Small Business Owners');
    }

    /**
     * TEST 21: SEO title and meta description render correctly.
     */
    public function test_seo_title_and_meta_behavior(): void
    {
        $response = $this->get('/courses/' . $this->publishedPaidCourse->slug);

        $response->assertStatus(200);
        $response->assertSee('<title>SEO Mastery for Small Business Owners - Online Marketing Education</title>', false);
        $response->assertSee('A practical framework to rank locally on Google without an agency.');
    }

    /**
     * TEST 22: Unauthorized access to unpublished archived course is blocked with 404.
     */
    public function test_unauthorized_access_to_archived_course_returns_404(): void
    {
        $response = $this->actingAs($this->student)->get('/courses/' . $this->archivedCourse->slug);

        $response->assertStatus(404);
    }
}