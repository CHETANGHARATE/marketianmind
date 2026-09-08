<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class CourseController extends Controller
{
    /**
     * Display the Courses catalog page.
     */
    public function index(): View
    {
        return view('public.courses.index');
    }

    /**
     * Display the Course Details page.
     */
    public function show(): View
    {
        return view('public.courses.show');
    }
}
