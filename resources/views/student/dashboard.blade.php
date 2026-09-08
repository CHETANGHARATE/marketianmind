@extends('layouts.student')

@section('subcontent')
<div class="rounded-xl border border-slate-200 bg-white p-8 shadow-sm">
    <div class="max-w-2xl">
        <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-700/10">
            Student Portal Module
        </span>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
            Student Dashboard (Architectural Placeholder)
        </h1>
        <p class="mt-3 text-sm text-slate-600 leading-relaxed">
            This section represents the isolated Student Portal environment. In subsequent development phases, this area will host purchased courses, video lessons, and progress tracking.
        </p>

        <div class="mt-6 flex items-center gap-3">
            <a href="{{ route('home') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white hover:bg-indigo-500 transition">
                Return to Public Site
            </a>
            <a href="{{ route('admin.dashboard') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                Switch to Admin Preview
            </a>
        </div>
    </div>
</div>
@endsection
