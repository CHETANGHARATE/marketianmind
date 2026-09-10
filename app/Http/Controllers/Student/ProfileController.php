<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\PasswordUpdateRequest;
use App\Http\Requests\Student\ProfileUpdateRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->fill([
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return back()->with('profile_status', 'Your profile information has been updated successfully.');
    }

    /**
     * Update the student password securely.
     */
    public function updatePassword(PasswordUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $request->session()->regenerate();

        return back()->with('password_status', 'Your password has been changed successfully.');
    }
}