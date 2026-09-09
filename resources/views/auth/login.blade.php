@extends('layouts.base', ['title' => 'Sign In - Marketian Mind'])

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
            Welcome Back
        </h2>
        <p class="mt-2 text-sm text-slate-600">
            Sign in to continue your practical marketing education
        </p>
    </div>

    <!-- Login Card -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4 sm:px-0">
        <div class="bg-white py-8 px-6 sm:px-10 shadow-sm border border-slate-200/80 rounded-2xl">
            <!-- Session Status Alert -->
            @if (session('status'))
                <div class="mb-5 rounded-lg bg-emerald-50 p-3.5 text-sm font-medium text-emerald-800 border border-emerald-200">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

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
                            autofocus
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
                    <div class="flex items-center justify-between">
                        <label for="password" class="block text-sm font-semibold text-slate-800">
                            Password
                        </label>
                        <a href="#" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                            Forgot password?
                        </a>
                    </div>
                    <div class="mt-1.5">
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="block w-full rounded-lg border @error('password') border-rose-400 focus:border-rose-500 focus:ring-rose-500 @else border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 @enderror px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-0 transition"
                        />
                    </div>
                    @error('password')
                        <p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="flex items-center justify-between">
                    <label for="remember" class="inline-flex items-center gap-2 cursor-pointer">
                        <input
                            id="remember"
                            type="checkbox"
                            name="remember"
                            class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 transition"
                        />
                        <span class="text-sm text-slate-600">Remember me</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div>
                    <button
                        type="submit"
                        class="w-full flex justify-center items-center rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-600 focus:ring-offset-2 transition"
                    >
                        Sign In
                    </button>
                </div>
            </form>

            <!-- Card Footer -->
            <div class="mt-6 pt-6 border-t border-slate-100 text-center text-sm text-slate-600">
                Don't have an account?
                <a href="{{ route('register') }}" class="font-semibold text-indigo-600 hover:text-indigo-500 transition">
                    Create an account
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