<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Display the student profile and account settings page.
     */
    public function show(Request $request): View
    {
        return view('student.profile', [
            'user' => $request->user(),
            'headerTitle' => 'Profile & Account Settings',
        ]);
    }

    /**
     * Update the student profile information (name and email).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        // Only update name and email - never permit role alteration
        $user->forceFill([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
        ])->save();

        return back()->with('profile_status', 'Your profile information has been updated successfully.');
    }

    /**
     * Update the student password securely.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ], [
            'current_password.current_password' => 'The current password you provided does not match our records.',
            'password.different' => 'Your new password must be different from your current password.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('password_status', 'Your password has been changed successfully.');
    }
}