<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseReviewStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseReviewController extends Controller
{
    /**
     * Display a paginated listing of course reviews with moderation controls.
     */
    public function index(Request $request): View
    {
        $query = CourseReview::query()->with(['course:id,title,slug', 'user:id,name,email']);

        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('review', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if ($courseId = $request->input('course_id')) {
            $query->where('course_id', $courseId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($rating = $request->input('rating')) {
            $query->where('rating', (int) $rating);
        }

        $reviews = $query->latest()->paginate(15)->withQueryString();

        // Platform Review KPI Metrics
        $totalReviews = CourseReview::count();
        $approvedReviews = CourseReview::where('status', CourseReviewStatus::APPROVED->value)->count();
        $hiddenReviews = CourseReview::where('status', CourseReviewStatus::HIDDEN->value)->count();
        $avgRating = CourseReview::where('status', CourseReviewStatus::APPROVED->value)->avg('rating');

        $metrics = [
            'total' => $totalReviews,
            'approved' => $approvedReviews,
            'hidden' => $hiddenReviews,
            'average_rating' => $avgRating ? round((float) $avgRating, 1) : 0.0,
        ];

        $courses = Course::select('id', 'title')->orderBy('title')->get();

        return view('admin.reviews.index', compact('reviews', 'metrics', 'courses'));
    }

    /**
     * Toggle the visibility (approved / hidden) of a student review.
     */
    public function toggleStatus(CourseReview $review): RedirectResponse
    {
        $newStatus = $review->status === CourseReviewStatus::APPROVED
            ? CourseReviewStatus::HIDDEN
            : CourseReviewStatus::APPROVED;

        $review->update(['status' => $newStatus]);

        AuditLogger::log(
            'review_status_toggled',
            $review,
            "Review #{$review->id} for '{$review->course?->title}' by '{$review->user?->name}' was set to {$newStatus->value}."
        );

        $statusLabel = $newStatus === CourseReviewStatus::APPROVED ? 'Approved (Visible)' : 'Hidden';

        return redirect()->back()
            ->with('status', "Review #{$review->id} has been set to {$statusLabel}.");
    }

    /**
     * Delete an abusive or spam review.
     */
    public function destroy(CourseReview $review): RedirectResponse
    {
        $id = $review->id;
        $courseTitle = $review->course?->title ?? 'Course';

        AuditLogger::log(
            'review_deleted',
            $review,
            "Review #{$id} for '{$courseTitle}' was deleted by administrator."
        );

        $review->delete();

        return redirect()->back()
            ->with('status', "Review #{$id} has been permanently deleted.");
    }
}