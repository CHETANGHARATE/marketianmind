<?php

namespace Tests\Feature;

use App\Enums\CourseReviewStatus;
use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCourseReviewTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->student = User::factory()->create([
            'role' => UserRole::STUDENT,
        ]);

        $this->course = Course::create([
            'title' => 'Copywriting Secrets',
            'slug' => 'copywriting-secrets-' . uniqid(),
            'short_description' => 'Write high-converting sales copy.',
            'price' => 1499.00,
            'is_free' => false,
            'status' => CourseStatus::PUBLISHED,
        ]);
    }

    public function test_guest_cannot_access_admin_reviews(): void
    {
        $response = $this->get(route('admin.reviews.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_reviews(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.reviews.index'));
        $response->assertForbidden();
    }

    public function test_admin_can_view_reviews_index(): void
    {
        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'rating' => 5,
            'review' => 'Excellent course on copywriting tactics.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reviews.index'));

        $response->assertOk();
        $response->assertSee('Course Reviews &amp; Ratings', false);
        $response->assertSee('Excellent course on copywriting tactics.');
        $response->assertSee($this->student->name);
    }

    public function test_admin_can_filter_reviews_by_rating_and_status(): void
    {
        $student2 = User::factory()->create(['role' => UserRole::STUDENT]);

        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'rating' => 5,
            'review' => 'Five star review content.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $student2->id,
            'rating' => 1,
            'review' => 'One star review content.',
            'status' => CourseReviewStatus::HIDDEN,
        ]);

        // Filter by rating 5
        $resp5 = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['rating' => 5]));
        $resp5->assertSee('Five star review content.');
        $resp5->assertDontSee('One star review content.');

        // Filter by status hidden
        $respHidden = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['status' => 'hidden']));
        $respHidden->assertSee('One star review content.');
        $respHidden->assertDontSee('Five star review content.');
    }

    public function test_admin_can_search_reviews_by_keyword(): void
    {
        CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'rating' => 5,
            'review' => 'Superb analytics walkthrough.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        $searchResp = $this->actingAs($this->admin)->get(route('admin.reviews.index', ['search' => 'analytics']));
        $searchResp->assertSee('Superb analytics walkthrough.');
    }

    public function test_admin_can_toggle_review_status_between_approved_and_hidden(): void
    {
        $review = CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'rating' => 5,
            'review' => 'Toggle review test content.',
            'status' => CourseReviewStatus::APPROVED,
        ]);

        // Toggle to hidden
        $response = $this->actingAs($this->admin)->patch(route('admin.reviews.toggle', $review));
        $response->assertRedirect();

        $review->refresh();
        $this->assertTrue($review->isHidden());

        // Toggle back to approved
        $this->actingAs($this->admin)->patch(route('admin.reviews.toggle', $review));
        $review->refresh();
        $this->assertTrue($review->isApproved());

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'review_status_toggled',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_admin_can_delete_review(): void
    {
        $review = CourseReview::create([
            'course_id' => $this->course->id,
            'user_id' => $this->student->id,
            'rating' => 1,
            'review' => 'Spam review to be deleted.',
            'status' => CourseReviewStatus::HIDDEN,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.reviews.destroy', $review));
        $response->assertRedirect();

        $this->assertDatabaseMissing('course_reviews', ['id' => $review->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'review_deleted',
            'user_id' => $this->admin->id,
        ]);
    }
}