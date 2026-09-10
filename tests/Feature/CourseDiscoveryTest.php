<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected User $otherStudent;
    protected User $admin;
    protected CourseCategory $categoryDigital;
    protected CourseCategory $categorySocial;
    protected Course $publishedPaidCourse;
    protected Course $publishedFreeCourse;
    protected Course $draftCourse;

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

        $this->categoryDigital = CourseCategory::create([
            'name' => 'Digital Marketing',
            'slug' => 'digital-marketing',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $this->categorySocial = CourseCategory::create([
            'name' => 'Social Media',
            'slug' => 'social-media',
            'status' => CategoryStatus::ACTIVE,
        ]);

        $this->publishedPaidCourse = Course::create([
            'course_category_id' => $this->categoryDigital->id,
            'title' => 'SEO Mastery for Small Business',
            'slug' => 'seo-mastery',
            'short_description' => 'Dominate local Google search rankings organically.',
            'instructor_name' => 'Sarah Content',
            'price' => 4999.00,
            'discount_price' => 3499.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
            'featured' => true,
        ]);

        $this->publishedFreeCourse = Course::create([
            'course_category_id' => $this->categorySocial->id,
            'title' => 'Instagram Organic Growth Blueprint',
            'slug' => 'instagram-growth',
            'short_description' => 'Scale your Instagram audience with zero ad spend.',
            'instructor_name' => 'David Reels',
            'price' => 0.00,
            'is_free' => true,
            'status' => CourseStatus::PUBLISHED,
            'featured' => false,
        ]);

        $this->draftCourse = Course::create([
            'course_category_id' => $this->categoryDigital->id,
            'title' => 'Top Secret Future Playbook',
            'slug' => 'secret-playbook',
            'short_description' => 'Unpublished draft course.',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::DRAFT,
            'featured' => false,
        ]);
    }

    /**
     * TEST 1: Guest can access course catalog.
     */
    public function test_guest_can_access_course_catalog(): void
    {
        $response = $this->get('/courses');

        $response->assertStatus(200);
        $response->assertSee('Explore Practical Marketing Programs');
        $response->assertSee('SEO Mastery for Small Business');
        $response->assertSee('Instagram Organic Growth Blueprint');
    }

    /**
     * TEST 2: Authenticated student can access course catalog.
     */
    public function test_authenticated_student_can_access_course_catalog(): void
    {
        $response = $this->actingAs($this->student)->get('/courses');

        $response->assertStatus(200);
        $response->assertSee('SEO Mastery for Small Business');
    }

    /**
     * TEST 3 & 4: Only published courses appear; draft/unpublished do not appear.
     */
    public function test_only_published_courses_appear_and_draft_courses_are_hidden(): void
    {
        $response = $this->get('/courses');

        $response->assertStatus(200);
        $response->assertSee('SEO Mastery for Small Business');
        $response->assertSee('Instagram Organic Growth Blueprint');
        $response->assertDontSee('Top Secret Future Playbook');
    }

    /**
     * TEST 5 & 6: Search works across title, description, and instructor.
     */
    public function test_search_returns_relevant_courses_by_title_description_or_instructor(): void
    {
        // Search by title
        $resTitle = $this->get('/courses?q=SEO');
        $resTitle->assertStatus(200);
        $resTitle->assertSee('SEO Mastery for Small Business');
        $resTitle->assertDontSee('Instagram Organic Growth Blueprint');

        // Search by instructor
        $resInstructor = $this->get('/courses?q=David');
        $resInstructor->assertStatus(200);
        $resInstructor->assertSee('Instagram Organic Growth Blueprint');
        $resInstructor->assertDontSee('SEO Mastery for Small Business');

        // Search by description keyword
        $resDesc = $this->get('/courses?q=organically');
        $resDesc->assertStatus(200);
        $resDesc->assertSee('SEO Mastery for Small Business');
    }

    /**
     * TEST 7: Empty search works normally without errors.
     */
    public function test_empty_search_works_normally(): void
    {
        $response = $this->get('/courses?q=' . urlencode('   '));

        $response->assertStatus(200);
        $response->assertSee('SEO Mastery for Small Business');
        $response->assertSee('Instagram Organic Growth Blueprint');
    }

    /**
     * TEST 8 & 9: Category filtering works and invalid category is handled safely.
     */
    public function test_category_filtering_works_and_invalid_category_is_handled_safely(): void
    {
        // Valid category filter
        $resCat = $this->get('/courses?category=digital-marketing');
        $resCat->assertStatus(200);
        $resCat->assertSee('SEO Mastery for Small Business');
        $resCat->assertDontSee('Instagram Organic Growth Blueprint');

        // Invalid category filter falls back to all courses gracefully
        $resInvalid = $this->get('/courses?category=nonexistent-hacker-slug');
        $resInvalid->assertStatus(200);
        $resInvalid->assertSee('SEO Mastery for Small Business');
        $resInvalid->assertSee('Instagram Organic Growth Blueprint');
    }

    /**
     * TEST 10 & 11: Free and Paid type filters work accurately.
     */
    public function test_free_and_paid_type_filters_work(): void
    {
        // Free filter
        $resFree = $this->get('/courses?type=free');
        $resFree->assertStatus(200);
        $resFree->assertSee('Instagram Organic Growth Blueprint');
        $resFree->assertDontSee('SEO Mastery for Small Business');

        // Paid filter
        $resPaid = $this->get('/courses?type=paid');
        $resPaid->assertStatus(200);
        $resPaid->assertSee('SEO Mastery for Small Business');
        $resPaid->assertDontSee('Instagram Organic Growth Blueprint');
    }

    /**
     * TEST 12 & 13: Sorting works and invalid sort value cannot manipulate SQL.
     */
    public function test_sorting_works_and_invalid_sort_value_is_safely_ignored(): void
    {
        // Test valid sort options
        $this->get('/courses?sort=newest')->assertStatus(200);
        $this->get('/courses?sort=oldest')->assertStatus(200);
        $this->get('/courses?sort=price_low')->assertStatus(200);
        $this->get('/courses?sort=price_high')->assertStatus(200);
        $this->get('/courses?sort=featured')->assertStatus(200);

        // Test invalid SQL injection attempt in sort parameter
        $resHacker = $this->get('/courses?sort=id+DESC;--DROP+TABLE+users;');
        $resHacker->assertStatus(200);
        $resHacker->assertSee('SEO Mastery for Small Business');
    }

    /**
     * TEST 14 & 15: Pagination works and query parameters persist across pagination.
     */
    public function test_pagination_works_and_filters_persist_across_pages(): void
    {
        // Create 12 more published courses in digital category to exceed page size of 9
        for ($i = 1; $i <= 12; $i++) {
            $course = Course::create([
                'course_category_id' => $this->categoryDigital->id,
                'title' => sprintf('Paginated Course %02d', $i),
                'slug' => sprintf('paginated-course-%02d', $i),
                'short_description' => 'Test course pagination description.',
                'price' => 1000.00,
                'is_free' => false,
                'status' => CourseStatus::PUBLISHED,
            ]);
            $course->created_at = now()->subMinutes(30 - $i);
            $course->save();
        }

        // Page 1
        $resPage1 = $this->get('/courses?category=digital-marketing');
        $resPage1->assertStatus(200);
        $resPage1->assertSee('Paginated Course 12');

        // Page 2
        $resPage2 = $this->get('/courses?category=digital-marketing&page=2');
        $resPage2->assertStatus(200);
        $resPage2->assertSee('category=digital-marketing');
    }

    /**
     * TEST 16: Empty results state displays informative empty state.
     */
    public function test_no_results_state_displays_informative_message_and_reset_button(): void
    {
        $response = $this->get('/courses?q=UnmatchedQueryTermXYZ');

        $response->assertStatus(200);
        $response->assertSee('No courses found matching your criteria');
        $response->assertSee('Clear Filters &amp; View All', false);
        $response->assertSee(route('courses'));
    }

    /**
     * TEST 17 & 18: Authenticated student sees correct enrollment state and does not see another student's enrollment.
     */
    public function test_authenticated_student_sees_correct_enrollment_state_with_isolation(): void
    {
        // Student enrolls in SEO course
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->publishedPaidCourse->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        // Student views catalog
        $response = $this->actingAs($this->student)->get('/courses');
        $response->assertStatus(200);
        $response->assertSee('Enrolled');
        $response->assertSee('Continue Learning');

        // Other student views catalog and should NOT see "Enrolled" for SEO course
        $otherResponse = $this->actingAs($this->otherStudent)->get('/courses');
        $otherResponse->assertStatus(200);
        $otherResponse->assertDontSee('Enrolled');
        $otherResponse->assertSee('View Course');
    }

    /**
     * TEST 19: Course cards link to the correct existing course detail route.
     */
    public function test_course_cards_link_to_correct_course_detail_route(): void
    {
        $response = $this->get('/courses');

        $response->assertStatus(200);
        $response->assertSee(route('courses.show', $this->publishedPaidCourse));
        $response->assertSee(route('courses.show', $this->publishedFreeCourse));
    }

    /**
     * TEST 20: Guests do not receive protected learning access merely by browsing catalog.
     */
    public function test_guests_do_not_receive_protected_learning_access_merely_by_browsing(): void
    {
        $response = $this->get('/courses');

        $response->assertStatus(200);
        $response->assertDontSee('Continue Learning');
        $response->assertDontSee('Review Course');

        // Attempting to access learning player directly still requires authentication
        $this->get('/student/courses/' . $this->publishedPaidCourse->id)->assertRedirect('/login');
    }

    /**
     * TEST 21: Admin course management functionality remains unaffected.
     */
    public function test_admin_course_management_remains_unaffected(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/courses');

        $response->assertStatus(200);
        // Admin sees both published and draft courses
        $response->assertSee('SEO Mastery for Small Business');
        $response->assertSee('Top Secret Future Playbook');
    }
}
