<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ContactController extends Controller
{
    /**
     * Display the Contact page.
     */
    public function index(): View
    {
        return view('public.contact');
    }
}
