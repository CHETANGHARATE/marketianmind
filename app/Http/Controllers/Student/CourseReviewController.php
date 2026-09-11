<?php

namespace App\Http\Controllers\Student;

use App\Enums\CourseReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseReviewController extends Controller
{
    /**
     * Store or update a student's review for an enrolled course.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isEnrolledIn($course)) {
            abort(403, 'You must be legitimately enrolled in this course to leave a review.');
        }

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'review' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        CourseReview::updateOrCreate(
            [
                'course_id' => $course->id,
                'user_id' => $user->id,
            ],
            [
                'rating' => $validated['rating'],
                'review' => trim($validated['review']),
                'status' => CourseReviewStatus::APPROVED,
            ]
        );

        return redirect()->back()
            ->with('status', 'Thank you! Your course rating and review have been saved.');
    }

    /**
     * Delete the authenticated student's review.
     */
    public function destroy(Request $request, Course $course, CourseReview $review): RedirectResponse
    {
        $user = $request->user();

        if ($review->user_id !== $user->id || $review->course_id !== $course->id) {
            abort(403, 'Unauthorized access to this review.');
        }

        $review->delete();

        return redirect()->back()
            ->with('status', 'Your review has been successfully removed.');
    }
}