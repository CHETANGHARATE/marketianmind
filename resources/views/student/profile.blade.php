@extends('layouts.student')

@section('subcontent')
<div class="space-y-8 max-w-4xl">
    <!-- Header -->
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">
            Student Profile
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Manage your personal profile, email address, and account security settings.
        </p>
    </div>

    <!-- User Header Card -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center gap-6">
            <!-- Avatar Placeholder (Initials) -->
            <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-indigo-600 text-white font-black text-2xl shadow-sm">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-slate-900">
                    {{ $user->name }}
                </h2>
                <p class="text-sm text-slate-500">
                    {{ $user->email }}
                </p>
                <div class="mt-2.5 flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 border border-indigo-100">
                        {{ $user->role->label() }}
                    </span>
                    <span class="inline-flex items-center rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-100">
                        Active Account
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- FORM 1: Personal Information -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm space-y-6">
        <div>
            <h3 class="text-lg font-bold text-slate-900">
                Personal Information
            </h3>
            <p class="mt-1 text-xs text-slate-500">
                Update your account display name and primary contact email address.
            </p>
        </div>

        @if (session('profile_status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('profile_status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('student.profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Full Name -->
            <div>
                <label for="name" class="block text-sm font-semibold text-slate-800">
                    Full Name
                </label>
                <div class="mt-1.5 max-w-lg">
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name', $user->name) }}"
                        required
                        autocomplete="name"
                        class="block w-full rounded-lg border @error('name') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                    />
                </div>
                @error('name')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-semibold text-slate-800">
                    Email Address
                </label>
                <div class="mt-1.5 max-w-lg">
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email', $user->email) }}"
                        required
                        autocomplete="username"
                        class="block w-full rounded-lg border @error('email') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                    />
                </div>
                @error('email')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition cursor-pointer"
                >
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- FORM 2: Security & Password -->
    <div id="settings" class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm space-y-6">
        <div>
            <h3 class="text-lg font-bold text-slate-900">
                Security &amp; Password
            </h3>
            <p class="mt-1 text-xs text-slate-500">
                Ensure your account is using a secure password to protect your learning progress.
            </p>
        </div>

        @if (session('password_status'))
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-sm font-medium text-emerald-800 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ session('password_status') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('student.profile.password.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Current Password -->
            <div>
                <label for="current_password" class="block text-sm font-semibold text-slate-800">
                    Current Password
                </label>
                <div class="mt-1.5 max-w-lg">
                    <input
                        id="current_password"
                        type="password"
                        name="current_password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="block w-full rounded-lg border @error('current_password') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                    />
                </div>
                @error('current_password')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- New Password -->
            <div>
                <label for="password" class="block text-sm font-semibold text-slate-800">
                    New Password
                </label>
                <div class="mt-1.5 max-w-lg">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        placeholder="Minimum 8 characters"
                        class="block w-full rounded-lg border @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                    />
                </div>
                @error('password')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm New Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-semibold text-slate-800">
                    Confirm New Password
                </label>
                <div class="mt-1.5 max-w-lg">
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Re-enter new password"
                        class="block w-full rounded-lg border @error('password_confirmation') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                    />
                </div>
                @error('password_confirmation')
                    <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-2">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition cursor-pointer"
                >
                    Update Password
                </button>
            </div>
        </form>
    </div>

    <!-- SECTION 3: Account Information (Read-Only) -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm space-y-4">
        <div>
            <h3 class="text-base font-bold text-slate-900">
                Account Information
            </h3>
            <p class="mt-1 text-xs text-slate-500">
                System metadata associated with your Marketian Mind student registration.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 pt-4 border-t border-slate-100">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Account Role</span>
                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $user->role->label() }}</p>
            </div>

            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Member Since</span>
                <p class="mt-1 text-sm font-semibold text-slate-900">
                    {{ $user->created_at ? $user->created_at->format('F d, Y') : 'N/A' }}
                </p>
            </div>

            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Status</span>
                <p class="mt-1 text-sm font-semibold text-emerald-600">Active</p>
            </div>
        </div>
    </div>
</div>
@endsection