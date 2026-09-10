@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Top Action Bar (hidden when printing) -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 print:hidden bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-2xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('student.courses') }}" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-indigo-600 transition">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to My Courses
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-xs font-semibold text-slate-500">Certificate {{ $certificate->certificate_number }}</span>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-2xs hover:bg-indigo-500 transition cursor-pointer"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Printable Certificate Canvas / Document -->
    <div id="certificate-printable-area" class="relative bg-white rounded-3xl border-8 border-double border-slate-800 p-8 sm:p-14 md:p-16 shadow-lg overflow-hidden text-center max-w-4xl mx-auto">
        <!-- Subtle Decorative Corner Accents -->
        <div class="absolute top-3 left-3 w-10 h-10 border-t-2 border-l-2 border-amber-600/60 pointer-events-none"></div>
        <div class="absolute top-3 right-3 w-10 h-10 border-t-2 border-r-2 border-amber-600/60 pointer-events-none"></div>
        <div class="absolute bottom-3 left-3 w-10 h-10 border-b-2 border-l-2 border-amber-600/60 pointer-events-none"></div>
        <div class="absolute bottom-3 right-3 w-10 h-10 border-b-2 border-r-2 border-amber-600/60 pointer-events-none"></div>

        <!-- Inner Frame Border -->
        <div class="border border-amber-700/30 p-6 sm:p-10 rounded-2xl relative bg-radial from-amber-50/20 via-white to-white">
            <!-- Brand Emblem & Organization Header -->
            <div class="flex flex-col items-center justify-center space-y-2 mb-6">
                <div class="flex items-center gap-2 font-black text-xl text-slate-900 tracking-tight">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base shadow-sm">
                        M
                    </span>
                    <span>Marketian<span class="text-indigo-600">Mind</span></span>
                </div>
                <p class="text-[10px] sm:text-xs uppercase tracking-[0.25em] font-bold text-slate-500">
                    Online Marketing Education for Founders &amp; Business Owners
                </p>
            </div>

            <!-- Certificate Headline -->
            <div class="my-6 space-y-2">
                <h1 class="text-2xl sm:text-4xl font-serif font-black tracking-wide text-slate-900 uppercase">
                    Certificate of Completion
                </h1>
                <div class="h-0.5 w-24 bg-gradient-to-r from-transparent via-amber-600 to-transparent mx-auto"></div>
            </div>

            <!-- Recipient Announcement -->
            <p class="text-xs sm:text-sm text-slate-600 italic font-serif">
                This is to officially certify that
            </p>

            <!-- Student Name -->
            <div class="my-4">
                <h2 class="text-2xl sm:text-4xl md:text-5xl font-serif font-black text-slate-900 tracking-tight pb-2 border-b-2 border-slate-300 inline-block px-8 max-w-full truncate">
                    {{ $certificate->student_name }}
                </h2>
            </div>

            <!-- Course Achievement Statement -->
            <p class="text-xs sm:text-sm text-slate-600 max-w-lg mx-auto leading-relaxed font-serif">
                has successfully completed all required modules, practical coursework, and curriculum milestones for
            </p>

            <!-- Course Title -->
            <div class="my-4">
                <h3 class="text-xl sm:text-2xl md:text-3xl font-bold text-indigo-700 tracking-tight px-4">
                    {{ $certificate->course_title }}
                </h3>
            </div>

            <!-- Verification & Issuance Metadata Grid -->
            <div class="mt-10 pt-8 border-t border-slate-200/90 grid grid-cols-1 sm:grid-cols-3 gap-6 items-end">
                <!-- Date of Completion -->
                <div class="text-center sm:text-left space-y-1">
                    <p class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Date Completed</p>
                    <p class="text-xs sm:text-sm font-semibold text-slate-800">
                        {{ $certificate->course_completion_date ? $certificate->course_completion_date->format('F d, Y') : $certificate->issued_at->format('F d, Y') }}
                    </p>
                </div>

                <!-- Official Seal Graphic -->
                <div class="flex flex-col items-center justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-amber-600 text-white shadow-md border-2 border-amber-300">
                        <svg class="h-8 w-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <span class="text-[9px] uppercase tracking-widest font-black text-amber-700 mt-1.5">Official Record</span>
                </div>

                <!-- Instructor / Faculty -->
                <div class="text-center sm:text-right space-y-1">
                    <p class="text-[10px] uppercase tracking-wider font-bold text-slate-400">Instructor / Faculty</p>
                    <p class="text-xs sm:text-sm font-semibold text-slate-800">
                        {{ $certificate->instructor_name ?? 'Marketian Mind Faculty' }}
                    </p>
                </div>
            </div>

            <!-- Footer Certificate Identification Number -->
            <div class="mt-8 pt-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between text-[11px] text-slate-400 gap-2">
                <span>Certificate No: <strong class="text-slate-600 font-mono">{{ $certificate->certificate_number }}</strong></span>
                <span>Verified Online &bull; Marketian Mind Learning</span>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    body {
        background-color: white !important;
        color: black !important;
    }
    aside, header, #mobile-sidebar-drawer, .print\:hidden {
        display: none !important;
    }
    main {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    #certificate-printable-area {
        box-shadow: none !important;
        border-width: 4px !important;
        max-width: 100% !important;
        margin: 0 auto !important;
        page-break-inside: avoid;
    }
}
</style>
@endpush
@endsection