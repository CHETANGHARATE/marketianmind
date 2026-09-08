<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class AboutController extends Controller
{
    /**
     * Display the About page.
     */
    public function index(): View
    {
        return view('public.about');
    }
}
