<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Schema;

class HomeController extends Controller
{
    /**
     * Display the Marketian Mind welcome / landing page.
     */
    public function index(): View
    {
        $featuredCourses = collect();
        if (Schema::hasTable('courses')) {
            $featuredCourses = Course::query()
                ->published()
                ->where('featured', true)
                ->with(['category', 'instructor'])
                ->withCount(['modules', 'lessons'])
                ->latest()
                ->take(3)
                ->get();

            // If no featured courses explicitly set, fallback to the latest published courses
            if ($featuredCourses->isEmpty()) {
                $featuredCourses = Course::query()
                    ->published()
                    ->with(['category', 'instructor'])
                    ->withCount(['modules', 'lessons'])
                    ->latest()
                    ->take(3)
                    ->get();
            }
        }

        // Recent top approved reviews for social proof testimonials
        $featuredReviews = collect();
        if (Schema::hasTable('course_reviews')) {
            $featuredReviews = CourseReview::query()
                ->approved()
                ->where('rating', '>=', 4)
                ->with(['user', 'course'])
                ->latest()
                ->take(3)
                ->get();
        }

        return view('public.welcome', compact('featuredCourses', 'featuredReviews'));
    }
}