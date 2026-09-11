<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    /**
     * Display the student's saved courses in their wishlist.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $wishlists = $user->wishlists()
            ->with(['course' => function ($q) {
                $q->with('category')->withCount('modules');
            }])
            ->whereHas('course', function ($q) {
                $q->published();
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        return view('student.wishlist.index', compact('wishlists', 'user'));
    }

    /**
     * Add a course to the student's wishlist.
     */
    public function store(Request $request, Course $course): RedirectResponse
    {
        if (! $course->isPublished()) {
            abort(404, 'Course not found or unavailable.');
        }

        $request->user()->addToWishlist($course);

        return redirect()->back()
            ->with('status', "'{$course->title}' has been saved to your wishlist.");
    }

    /**
     * Remove a course from the student's wishlist.
     */
    public function destroy(Request $request, Course $course): RedirectResponse
    {
        $request->user()->removeFromWishlist($course);

        return redirect()->back()
            ->with('status', "'{$course->title}' has been removed from your wishlist.");
    }

    /**
     * Toggle saved state of a course in the student's wishlist.
     */
    public function toggle(Request $request, Course $course): RedirectResponse
    {
        if (! $course->isPublished()) {
            abort(404, 'Course not found or unavailable.');
        }

        $user = $request->user();

        if ($user->hasInWishlist($course)) {
            $user->removeFromWishlist($course);
            $message = "'{$course->title}' has been removed from your wishlist.";
        } else {
            $user->addToWishlist($course);
            $message = "'{$course->title}' has been saved to your wishlist.";
        }

        return redirect()->back()
            ->with('status', $message);
    }
}