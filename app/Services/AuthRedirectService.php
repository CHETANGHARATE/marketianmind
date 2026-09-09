<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AuthRedirectService
{
    /**
     * Redirect an authenticated user to their role-specific dashboard.
     */
    public static function toDashboard(User $user): RedirectResponse
    {
        return redirect()->intended($user->dashboardUrl());
    }
}