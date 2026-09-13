<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuthRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::STUDENT,
        ]);

        Auth::login($user);

        // Attribute referral if code provided or present in session/cookie
        $referralCode = $request->input('ref') ?? $request->input('referral_code');
        app(\App\Services\ReferralService::class)->attributeRegistration($user, $referralCode, $request->ip());

        // Auto-convert matching CRM leads
        app(\App\Services\LeadService::class)->autoConvertMatchingLeads($user, 'registration');

        app(\App\Services\TransactionalMailService::class)->sendWelcome($user);

        app(\App\Services\MarketingAutomationService::class)->dispatchTrigger(
            \App\Enums\AutomationTrigger::STUDENT_REGISTERED,
            $user,
            [],
            'student_registered_' . $user->id
        );

        return AuthRedirectService::toDashboard($user);
    }
}