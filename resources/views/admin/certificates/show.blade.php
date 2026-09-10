@extends('layouts.admin')

@section('subcontent')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Top Navigation & Audit Notice -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <a href="{{ route('admin.certificates.index') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to All Certificates
        </a>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-slate-800/80 border border-slate-700 text-[11px] text-slate-400">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            Audit Mode &bull; Immutable Credential Snapshot
        </div>
    </div>

    <!-- Certificate Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-6 border-b border-slate-800 gap-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-amber-400">Certificate of Completion</span>
                <h1 class="text-2xl sm:text-3xl font-black text-white mt-1 font-mono">{{ $certificate->certificate_number }}</h1>
                <p class="text-xs text-slate-400 mt-1">
                    Conferred to <strong class="text-white">{{ $certificate->student_name }}</strong> on {{ $certificate->issued_at ? $certificate->issued_at->format('M d, Y \a\t h:i A') : 'N/A' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('student.certificates.show', $certificate) }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-xs font-bold text-amber-400 hover:bg-amber-500/20 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    Student View &rarr;
                </a>
            </div>
        </div>

        <!-- Historical Snapshot Record Section -->
        <div class="py-6 border-b border-slate-800">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Historical Snapshot Record</span>
                <span class="text-[10px] text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20 font-semibold">Immutable</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
                <!-- Snapshot Student Name -->
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block mb-1 text-[11px]">Snapshot Student Name</span>
                    <p class="font-bold text-white text-base">{{ $certificate->student_name }}</p>
                    <p class="text-[10px] text-slate-500 mt-1 italic">Name printed on physical/digital credential</p>
                </div>

                <!-- Snapshot Course Title -->
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block mb-1 text-[11px]">Snapshot Course Title</span>
                    <p class="font-bold text-white text-base">{{ $certificate->course_title }}</p>
                    <p class="text-[10px] text-slate-500 mt-1 italic">Curriculum name at completion</p>
                </div>

                <!-- Snapshot Instructor -->
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block mb-1 text-[11px]">Snapshot Instructor</span>
                    <p class="font-bold text-white text-base">{{ $certificate->instructor_name ?: 'Marketian Mind Faculty' }}</p>
                    <p class="text-[10px] text-slate-500 mt-1 italic">Authorized faculty signee</p>
                </div>
            </div>

            <!-- Metadata & Timestamps Row -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs mt-4">
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block mb-1 text-[11px]">Completion Timestamp</span>
                    <p class="font-semibold text-slate-200">
                        {{ $certificate->course_completion_date ? $certificate->course_completion_date->format('M d, Y h:i A') : 'N/A' }}
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block mb-1 text-[11px]">Issuance Timestamp</span>
                    <p class="font-semibold text-slate-200">
                        {{ $certificate->issued_at ? $certificate->issued_at->format('M d, Y h:i A') : 'N/A' }}
                    </p>
                </div>

                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <span class="text-slate-500 block mb-1 text-[11px]">Curriculum Metadata</span>
                    <p class="font-semibold text-slate-200">
                        {{ $certificate->metadata['total_lessons'] ?? 'All' }} Lessons Completed &bull; {{ $certificate->metadata['duration'] ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Current Relational Context (Student & Course) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 py-6 border-b border-slate-800 text-xs">
            <!-- Current Student Account -->
            <div class="rounded-xl bg-slate-950/50 p-5 border border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Current Student Account</span>
                    <a href="{{ route('admin.students.show', $certificate->user) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        View Profile &rarr;
                    </a>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-sm font-bold text-amber-400 border border-slate-700">
                        {{ strtoupper(substr($certificate->user->name, 0, 1)) }}
                    </div>
                    <div>
                        <p class="font-bold text-white text-sm">{{ $certificate->user->name }}</p>
                        <p class="text-slate-400 text-xs">{{ $certificate->user->email }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span>User ID: #{{ $certificate->user->id }}</span>
                    <span>Account Verified: {{ $certificate->user->email_verified_at ? 'Yes' : 'No' }}</span>
                </div>
            </div>

            <!-- Current Course Catalog Record -->
            <div class="rounded-xl bg-slate-950/50 p-5 border border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Current Course Catalog</span>
                    <a href="{{ route('admin.courses.edit', $certificate->course) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        Edit Course &rarr;
                    </a>
                </div>
                <div>
                    <p class="font-bold text-white text-sm">{{ $certificate->course->title }}</p>
                    <p class="text-slate-400 text-xs mt-0.5">Slug: {{ $certificate->course->slug }}</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-400">
                    <span>Category: {{ $certificate->course->category?->name ?? 'Uncategorized' }}</span>
                    <span>Catalog Price: ₹{{ number_format($certificate->course->price, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Linked Enrollment Record -->
        <div class="pt-6 text-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
                Associated Enrollment Record
            </h3>
            @if($certificate->enrollment)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl bg-slate-950/60 border border-slate-800">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400 border border-emerald-500/20">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <span class="font-bold text-white text-sm">Enrollment Verified Completed</span>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Enrollment ID: #{{ $certificate->enrollment->id }} &bull; Enrolled {{ $certificate->enrollment->enrolled_at ? $certificate->enrollment->enrolled_at->format('M d, Y') : 'N/A' }} &bull; Completed {{ $certificate->enrollment->completed_at ? $certificate->enrollment->completed_at->format('M d, Y') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                    <div>
                        <a href="{{ route('admin.enrollments.show', $certificate->enrollment) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-700 bg-slate-800 px-3.5 py-1.5 text-xs font-semibold text-amber-400 hover:text-amber-300 hover:bg-slate-700 transition">
                            Inspect Enrollment &rarr;
                        </a>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 text-slate-400">
                    <span class="font-semibold text-slate-300">Enrollment reference not found.</span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection