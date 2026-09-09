@extends('layouts.base', ['title' => 'Create Your Account - Marketian Mind'])

@section('content')
<div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-slate-50">
    <!-- Brand Header -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2.5 font-bold text-2xl text-slate-900 tracking-tight transition hover:opacity-90">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-xl shadow-sm">
                M
            </span>
            <span>Marketian<span class="text-indigo-600">Mind</span></span>
        </a>
        <h2 class="mt-6 text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Create Your Account
        </h2>
        <p class="mt-2 text-sm text-slate-600">
            Start learning how to grow your business online without expensive agencies
        </p>
    </div>

    <!-- Registration Card -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 sm:px-10 shadow-sm border border-slate-200/80 rounded-2xl">
            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf

                <!-- Full Name -->
                <div>
                    <label for="name" class="block text-sm font-semibold text-slate-800">
                        Full Name
                    </label>
                    <div class="mt-1.5">
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="e.g. John Doe"
                            class="block w-full rounded-lg border @error('name') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
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
                    <div class="mt-1.5">
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="username"
                            placeholder="you@yourbusiness.com"
                            class="block w-full rounded-lg border @error('email') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                        />
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-semibold text-slate-800">
                        Password
                    </label>
                    <div class="mt-1.5">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Minimum 8 characters"
                            class="block w-full rounded-lg border @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                        />
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-semibold text-slate-800">
                        Confirm Password
                    </label>
                    <div class="mt-1.5">
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Re-enter password"
                            class="block w-full rounded-lg border @error('password_confirmation') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                        />
                    </div>
                    @error('password_confirmation')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Reassuring Note -->
                <div class="pt-2">
                    <p class="text-xs text-slate-500 leading-relaxed">
                        By creating an account, you get free student access to preview courses and track your progress.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button
                        type="submit"
                        class="w-full flex justify-center items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition"
                    >
                        Create Account
                    </button>
                </div>
            </form>

            <!-- Card Footer -->
            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-sm text-slate-600">
                Already have an account?
                <a href="{{ route('login') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition">
                    Sign in
                </a>
            </div>
        </div>

        <!-- Back to Home Link -->
        <div class="mt-6 text-center">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-slate-800 transition">
                &larr; Back to Marketian Mind home
            </a>
        </div>
    </div>
</div>
@endsection