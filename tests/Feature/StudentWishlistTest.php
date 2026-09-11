<?php

namespace Tests\Feature;

use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentWishlistTest extends TestCase
{
    use RefreshDatabase;

    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Digital Marketing System for Small Business',
            'slug' => 'digital-marketing-system-' . uniqid(),
            'short_description' => 'A practical, actionable framework.',
            'price' => 2499.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_guest_cannot_access_wishlist_page_and_is_redirected_to_login(): void
    {
        $response = $this->get(route('student.wishlist.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_student_can_view_empty_wishlist_page(): void
    {
        $response = $this->actingAs($this->student)->get(route('student.wishlist.index'));

        $response->assertOk();
        $response->assertSee('Wishlist');
        $response->assertSee('Your wishlist is empty');
    }

    public function test_student_can_add_course_to_wishlist(): void
    {
        $response = $this->actingAs($this->student)
            ->from(route('courses.show', $this->course->slug))
            ->post(route('student.wishlist.store', $this->course));

        $response->assertRedirect(route('courses.show', $this->course->slug));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
        $this->assertTrue($this->student->fresh()->hasInWishlist($this->course));
    }

    public function test_adding_same_course_twice_does_not_create_duplicate(): void
    {
        $this->actingAs($this->student)->post(route('student.wishlist.store', $this->course));
        $this->actingAs($this->student)->post(route('student.wishlist.store', $this->course));

        $this->assertEquals(1, Wishlist::where('user_id', $this->student->id)->where('course_id', $this->course->id)->count());
    }

    public function test_student_can_remove_course_from_wishlist(): void
    {
        $this->student->addToWishlist($this->course);
        $this->assertTrue($this->student->fresh()->hasInWishlist($this->course));

        $response = $this->actingAs($this->student)
            ->from(route('student.wishlist.index'))
            ->delete(route('student.wishlist.destroy', $this->course));

        $response->assertRedirect(route('student.wishlist.index'));
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
        ]);
        $this->assertFalse($this->student->fresh()->hasInWishlist($this->course));
    }

    public function test_student_can_toggle_course_in_wishlist(): void
    {
        // Toggle on
        $response1 = $this->actingAs($this->student)
            ->from(route('courses.show', $this->course->slug))
            ->post(route('student.wishlist.toggle', $this->course));

        $response1->assertRedirect(route('courses.show', $this->course->slug));
        $this->assertTrue($this->student->fresh()->hasInWishlist($this->course));

        // Toggle off
        $response2 = $this->actingAs($this->student)
            ->from(route('courses.show', $this->course->slug))
            ->post(route('student.wishlist.toggle', $this->course));

        $response2->assertRedirect(route('courses.show', $this->course->slug));
        $this->assertFalse($this->student->fresh()->hasInWishlist($this->course));
    }

    public function test_student_wishlist_page_displays_saved_courses(): void
    {
        $this->student->addToWishlist($this->course);

        $response = $this->actingAs($this->student)->get(route('student.wishlist.index'));

        $response->assertOk();
        $response->assertSee($this->course->title);
        $response->assertSee('₹' . number_format($this->course->price, 0));
    }

    public function test_wishlist_card_displays_enrolled_badge_if_student_is_enrolled(): void
    {
        Enrollment::create([
            'user_id' => $this->student->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);

        $this->student->addToWishlist($this->course);

        $response = $this->actingAs($this->student)->get(route('student.wishlist.index'));

        $response->assertOk();
        $response->assertSee('Enrolled');
    }

    public function test_cannot_add_unpublished_course_to_wishlist(): void
    {
        $draftCourse = Course::create([
            'title' => 'Draft Course Example',
            'slug' => 'draft-course-' . uniqid(),
            'short_description' => 'Draft description for testing.',
            'price' => 999.00,
            'is_free' => false,
            'status' => CourseStatus::DRAFT,
        ]);

        $response = $this->actingAs($this->student)->post(route('student.wishlist.store', $draftCourse));

        $response->assertNotFound();
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $this->student->id,
            'course_id' => $draftCourse->id,
        ]);
    }

    public function test_course_is_wishlisted_by_helper_method(): void
    {
        $this->assertFalse($this->course->isWishlistedBy($this->student));

        $this->student->addToWishlist($this->course);

        $this->assertTrue($this->course->fresh()->isWishlistedBy($this->student));
    }
}