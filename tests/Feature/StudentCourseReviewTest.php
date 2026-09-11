<?php

namespace Tests\Feature;

use App\Enums\CourseReviewStatus;
use App\Enums\CourseStatus;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentCourseReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $enrolledStudent;
    protected User $unenrolledStudent;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enrolledStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->unenrolledStudent = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Growth Hacking Masterclass',
            'slug' => 'growth-hacking-masterclass-' . uniqid(),
            'short_description' => 'Learn growth strategies for founders.',
            'price' => 1999.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);

        // Enroll the student
        Enrollment::create([
            'user_id' => $this->enrolledStudent->id,
            'course_id' => $this->course->id,
            'status' => EnrollmentStatus::ACTIVE,
            'enrolled_at' => now(),
        ]);
    }

    public function test_guest_cannot_submit_review_and_is_redirected_to_login(): void
    {
        $response = $this->post(route('student.courses.reviews.store', $this->course), [
            'rating' => 5,
            'review' => 'Exceptional practical frameworks!',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseEmpty('course_reviews');
    }

    public function test_unenrolled_student_cannot_submit_review(): void
    {
        $response = $this->actingAs($this->unenrolledStudent)
            ->post(route('student.courses.reviews.store', $this->course), [
                'rating' => 5,
                'review' => 'I have not bought this course.',
            ]);

        $response->assertForbidden();
        $this->assertDatabaseEmpty('course_reviews');
    }

    public function test_enrolled_student_can_submit_valid_review(): void
    {
        $response = $this->actingAs($this->enrolledStudent)
            ->post(route('student.courses.reviews.store', $this->course), [
                'rating' => 5,
                'review' => 'This course transformed our customer acquisition completely!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('course_reviews', [
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 5,
            'review' => 'This course transformed our customer acquisition completely!',
            'status' => CourseReviewStatus::APPROVED->value,
        ]);
    }

    public function test_review_validation_requires_valid_rating_1_to_5(): void
    {
        // Rating too high
        $response1 = $this->actingAs($this->enrolledStudent)
            ->post(route('student.courses.reviews.store', $this->course), [
                'rating' => 6,
                'review' => 'Way too good!',
            ]);
        $response1->assertSessionHasErrors(['rating']);

        // Rating too low
        $response2 = $this->actingAs($this->enrolledStudent)
            ->post(route('student.courses.reviews.store', $this->course), [
                'rating' => 0,
                'review' => 'Not good.',
            ]);
        $response2->assertSessionHasErrors(['rating']);
    }

    public function test_review_validation_requires_minimum_length_comment(): void
    {
        $response = $this->actingAs($this->enrolledStudent)
            ->post(route('student.courses.reviews.store', $this->course), [
                'rating' => 4,
                'review' => 'Hi', // Less than 5 characters
            ]);

        $response->assertSessionHasErrors(['review']);
    }

    public function test_student_can_update_existing_review_without_creating_duplicate(): void
    {
        // Submit initial review
        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 4,
            'review' => 'Initial good thoughts about the course.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        // Submit updated review
        $response = $this->actingAs($this->enrolledStudent)
            ->post(route('student.courses.reviews.store', $this->course), [
                'rating' => 5,
                'review' => 'Updated: finished the course and it is 5 stars now!',
            ]);

        $response->assertRedirect();

        // Ensure only 1 review exists for this user/course
        $this->assertEquals(1, CourseReview::where('course_id', $this->course->id)->where('user_id', $this->enrolledStudent->id)->count());

        $this->assertDatabaseHas('course_reviews', [
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 5,
            'review' => 'Updated: finished the course and it is 5 stars now!',
        ]);
    }

    public function test_student_can_delete_own_review(): void
    {
        $review = CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 5,
            'review' => 'Accidental review to be removed.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->enrolledStudent)
            ->delete(route('student.courses.reviews.destroy', [$this->course, $review]));

        $response->assertRedirect();
        $this->assertDatabaseMissing('course_reviews', ['id' => $review->id]);
    }

    public function test_student_cannot_delete_another_students_review(): void
    {
        $otherStudent = User::factory()->create(['role' => UserRole::STUDENT]);
        $review = CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 5,
            'review' => 'My protected review.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        $response = $this->actingAs($otherStudent)
            ->delete(route('student.courses.reviews.destroy', [$this->course, $review]));

        $response->assertForbidden();
        $this->assertDatabaseHas('course_reviews', ['id' => $review->id]);
    }

    public function test_approved_reviews_are_displayed_on_public_course_show_page(): void
    {
        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 5,
            'review' => 'Sensational course content for small businesses!',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        $response = $this->get(route('courses.show', $this->course));

        $response->assertOk();
        $response->assertSee('Sensational course content for small businesses!');
        $response->assertSee($this->enrolledStudent->name);
    }

    public function test_hidden_reviews_are_not_displayed_on_public_course_show_page(): void
    {
        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 1,
            'review' => 'Hidden spam content here.',
            'status' => CourseReviewStatus::HIDDEN,
        ]);

        $response = $this->get(route('courses.show', $this->course));

        $response->assertOk();
        $response->assertDontSee('Hidden spam content here.');
    }

    public function test_course_calculates_accurate_average_rating_and_rating_distribution(): void
    {
        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);
        $student3 = User::factory()->create(['role' => UserRole::STUDENT]);

        // Create 3 reviews: 5 stars, 4 stars, 5 stars -> avg = 4.7
        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->enrolledStudent->id,
            'rating' => 5,
            'review' => 'First review 5 stars.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $student2->id,
            'rating' => 4,
            'review' => 'Second review 4 stars.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $student3->id,
            'rating' => 5,
            'review' => 'Third review 5 stars.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        $this->assertEquals(4.7, $this->course->averageRating());
        $this->assertEquals(3, $this->course->reviewsCount());

        $dist = $this->course->ratingDistribution();
        $this->assertEquals(2, $dist[5]['count']);
        $this->assertEquals(67, $dist[5]['percentage']);
        $this->assertEquals(1, $dist[4]['count']);
        $this->assertEquals(33, $dist[4]['percentage']);
        $this->assertEquals(0, $dist[3]['count']);
        $this->assertEquals(0, $dist[3]['percentage']);
    }
}