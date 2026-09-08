<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Display the Admin Portal dashboard placeholder.
     */
    public function index(): View
    {
        return view('admin.dashboard');
    }
}
