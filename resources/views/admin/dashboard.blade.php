@extends('layouts.admin')

@section('subcontent')
<div class="rounded-xl border border-slate-800 bg-slate-950 p-8 shadow-sm">
    <div class="max-w-2xl">
        <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2.5 py-1 text-xs font-semibold text-amber-400 ring-1 ring-inset ring-amber-500/20">
            Admin Management Module
        </span>
        <h1 class="mt-4 text-2xl font-bold tracking-tight text-white sm:text-3xl">
            Admin Dashboard (Architectural Placeholder)
        </h1>
        <p class="mt-3 text-sm text-slate-400 leading-relaxed">
            This section represents the isolated Admin Portal environment. In subsequent development phases, this area will host course management, student enrollments, sales analytics, and video uploads.
        </p>

        <div class="mt-6 flex items-center gap-3">
            <a href="{{ route('home') }}" class="rounded-lg bg-amber-500 px-4 py-2 text-xs font-semibold text-slate-950 hover:bg-amber-400 transition">
                Return to Public Site
            </a>
            <a href="{{ route('student.dashboard') }}" class="rounded-lg border border-slate-700 px-4 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800 transition">
                Switch to Student Preview
            </a>
        </div>
    </div>
</div>
@endsection
