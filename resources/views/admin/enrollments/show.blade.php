@extends('layouts.admin')

@section('subcontent')
<div class="space-y-8">
    <!-- Breadcrumb & Back Action -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.enrollments.index') }}"
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-amber-400 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Enrollments & Renewals
        </a>

        <div class="flex items-center gap-2">
            @if($enrollment->user)
                <a href="{{ route('admin.students.show', $enrollment->user) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Student Profile
                </a>
            @endif
            @if($enrollment->course)
                <a href="{{ route('admin.courses.edit', $enrollment->course) }}"
                   class="inline-flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                    <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Course Settings
                </a>
            @endif
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if(session('success'))
        <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-medium text-emerald-400 flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs font-medium text-rose-400 flex items-center gap-3">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4 text-xs text-rose-400">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Enrollment Overview Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                        Enrollment Record #{{ $enrollment->id }}
                    </h1>
                    @php
                        $adminBadge = $enrollment->getAdminAccessBadgeDetails();
                        $statusVal = $enrollment->status?->value ?? $enrollment->status;
                        $statusBadgeClass = match($statusVal) {
                            'completed' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                            'cancelled' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                            'expired' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                            default => 'bg-blue-500/10 text-blue-400 border border-blue-500/30',
                        };
                    @endphp
                    <!-- Access Lifecycle Badge -->
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold uppercase tracking-wider {{ $adminBadge['classes'] }}">
                        {{ $adminBadge['label'] }}
                    </span>
                    <!-- Enrollment Status Badge -->
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold uppercase tracking-wider {{ $statusBadgeClass }}">
                        Status: {{ ucfirst($statusVal) }}
                    </span>
                    @if($progress['is_completed'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                            100% Curriculum Completed
                        </span>
                    @endif
                </div>
                <p class="mt-1.5 text-xs text-slate-400">
                    Enrolled on {{ $enrollment->enrolled_at?->format('F d, Y \a\t h:i A') ?? $enrollment->created_at?->format('F d, Y \a\t h:i A') }}
                    ({{ ($enrollment->enrolled_at ?? $enrollment->created_at)?->diffForHumans() }})
                </p>
            </div>

            <div class="flex items-center gap-6 sm:border-l sm:border-slate-800 sm:pl-6">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Access Expiration</p>
                    <p class="text-sm font-bold text-white mt-0.5">
                        @if($enrollment->isLegacyLifetimeAccess())
                            <span class="text-indigo-400">Permanent Lifetime</span>
                        @elseif($enrollment->expires_at)
                            <span class="{{ $enrollment->isAccessExpired() ? 'text-rose-400' : 'text-emerald-400' }}">
                                {{ $enrollment->expires_at->format('M d, Y') }}
                            </span>
                        @else
                            <span class="text-slate-500">Unspecified</span>
                        @endif
                    </p>
                    <p class="text-[11px] text-slate-500 mt-0.5">
                        {{ $enrollment->getRemainingDaysText() }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Access Governance & Manual Grant / Extension Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-6 border-b border-slate-800">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight flex items-center gap-2">
                    <span>Course Access Lifecycle & Extension Controls</span>
                </h2>
                <p class="text-xs text-slate-400 mt-1">
                    Manage finite course access validity periods. In accordance with platform policy, one-time course purchases grant 365 days of learning access. Manual extensions are audited.
                </p>
            </div>

            <div class="flex items-center gap-4 text-xs">
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-2.5">
                    <span class="text-slate-500 block text-[10px] uppercase font-semibold">Current Starts At</span>
                    <span class="text-white font-bold">{{ $enrollment->starts_at?->format('M d, Y') ?? 'N/A (Lifetime)' }}</span>
                </div>
                <div class="rounded-xl border border-slate-800 bg-slate-950/60 px-4 py-2.5">
                    <span class="text-slate-500 block text-[10px] uppercase font-semibold">Current Expires At</span>
                    <span class="{{ $enrollment->isAccessExpired() ? 'text-rose-400 font-bold' : 'text-white font-bold' }}">
                        {{ $enrollment->expires_at?->format('M d, Y') ?? 'N/A (Lifetime)' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-6">
            @if($enrollment->isLegacyLifetimeAccess())
                <div class="rounded-xl border border-indigo-500/30 bg-indigo-500/10 p-4 text-xs text-indigo-300 flex items-start gap-3">
                    <svg class="w-5 h-5 shrink-0 text-indigo-400 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-bold text-indigo-200">Protected Legacy Lifetime Enrollment</p>
                        <p class="mt-1 leading-relaxed text-indigo-300/90">
                            This student was enrolled under the legacy lifetime access model. Their learning access does not expire. To preserve student rights and prevent accidental degradation of service, administrative finite extensions are locked.
                        </p>
                    </div>
                </div>
            @else
                <!-- Extension Form -->
                <form method="POST" action="{{ route('admin.enrollments.extend-access', $enrollment) }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">

                    <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-start">
                        <!-- Days Input & Presets (5 cols) -->
                        <div class="md:col-span-4 space-y-2">
                            <label for="access_days" class="block text-xs font-semibold text-slate-300">
                                Additional Validity Duration (Days)
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="access_days"
                                       name="days"
                                       min="1"
                                       max="3650"
                                       value="{{ old('days', 365) }}"
                                       required
                                       class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-500 text-xs">
                                    days
                                </div>
                            </div>

                            <!-- Duration Quick Presets -->
                            <div class="flex items-center gap-1.5 pt-1">
                                <span class="text-[10px] text-slate-500">Presets:</span>
                                <button type="button" onclick="document.getElementById('access_days').value = 30"
                                        class="px-2 py-0.5 rounded-md bg-slate-800 text-[10px] text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                    30d
                                </button>
                                <button type="button" onclick="document.getElementById('access_days').value = 90"
                                        class="px-2 py-0.5 rounded-md bg-slate-800 text-[10px] text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                    90d
                                </button>
                                <button type="button" onclick="document.getElementById('access_days').value = 180"
                                        class="px-2 py-0.5 rounded-md bg-slate-800 text-[10px] text-slate-300 hover:bg-slate-700 hover:text-white transition">
                                    180d
                                </button>
                                <button type="button" onclick="document.getElementById('access_days').value = 365"
                                        class="px-2 py-0.5 rounded-md bg-amber-500/20 text-[10px] text-amber-400 hover:bg-amber-500/30 transition font-semibold">
                                    365d (1 Yr)
                                </button>
                            </div>
                        </div>

                        <!-- Reason Input (5 cols) -->
                        <div class="md:col-span-5 space-y-2">
                            <label for="access_reason" class="block text-xs font-semibold text-slate-300">
                                Administrative Justification / Reason <span class="text-rose-400">*</span>
                            </label>
                            <input type="text"
                                   id="access_reason"
                                   name="reason"
                                   placeholder="e.g. Customer support extension, bonus promotion, medical leave..."
                                   value="{{ old('reason') }}"
                                   required
                                   minlength="5"
                                   maxlength="500"
                                   class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                            <p class="text-[10px] text-slate-500">
                                This justification is permanently recorded in the administrative audit log.
                            </p>
                        </div>

                        <!-- Submit Button (3 cols) -->
                        <div class="md:col-span-3 pt-6">
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 focus:outline-hidden focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 focus:ring-offset-slate-900 transition shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                <span>Grant / Extend Access</span>
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <!-- 2-Column Grid: Student & Course Information -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Student Information Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <h2 class="text-base font-bold text-white tracking-tight">Student Details</h2>
                @if($enrollment->user)
                    <a href="{{ route('admin.students.show', $enrollment->user) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        View Full Profile &rarr;
                    </a>
                @endif
            </div>

            @if($enrollment->user)
                <div class="mt-4 flex items-start gap-4">
                    <div class="h-12 w-12 rounded-full bg-amber-500/20 text-amber-400 font-bold text-sm flex items-center justify-center shrink-0 border border-amber-500/30">
                        {{ strtoupper(substr($enrollment->user->name, 0, 1)) }}
                    </div>
                    <div class="space-y-1 min-w-0">
                        <p class="text-sm font-bold text-white">{{ $enrollment->user->name }}</p>
                        <p class="text-xs text-slate-400">{{ $enrollment->user->email }}</p>
                        <div class="pt-2 flex items-center gap-2 flex-wrap text-[11px]">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-800 text-slate-300">
                                Role: {{ ucfirst($enrollment->user->role?->value ?? $enrollment->user->role) }}
                            </span>
                            @if($enrollment->user->email_verified_at)
                                <span class="text-emerald-400">Verified Account</span>
                            @else
                                <span class="text-slate-500">Unverified Email</span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <p class="mt-4 text-xs text-slate-500">Student record no longer exists.</p>
            @endif
        </div>

        <!-- Course Information Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <h2 class="text-base font-bold text-white tracking-tight">Course Information</h2>
                @if($enrollment->course)
                    <a href="{{ route('admin.courses.edit', $enrollment->course) }}" class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                        Edit Course &rarr;
                    </a>
                @endif
            </div>

            @if($enrollment->course)
                <div class="mt-4 space-y-3">
                    <div>
                        <p class="text-sm font-bold text-white">{{ $enrollment->course->title }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $enrollment->course->category?->name ?? 'Uncategorized' }}</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs pt-1">
                        <div>
                            <span class="text-slate-500">Access Tier:</span>
                            @if($enrollment->course->is_free)
                                <span class="font-bold text-emerald-400 ml-1">Free Access</span>
                            @else
                                <span class="font-bold text-white ml-1">₹{{ number_format($enrollment->course->effectivePrice(), 2) }}</span>
                            @endif
                        </div>
                        <span class="text-slate-700">&bull;</span>
                        <div>
                            <span class="text-slate-500">Validity Standard:</span>
                            <span class="font-semibold text-slate-300 ml-1">{{ $enrollment->course->access_validity_days ?? 365 }} Days</span>
                        </div>
                    </div>
                </div>
            @else
                <p class="mt-4 text-xs text-slate-500">Course record is unavailable.</p>
            @endif
        </div>
    </div>

    <!-- 2-Column Grid: Learning Progress & Credentials -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Learning Progress Breakdown -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Learning Progress</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Permanent progress and completion record</p>
                </div>
                <span class="text-lg font-black text-amber-400">{{ $progress['percentage'] }}%</span>
            </div>

            <div class="mt-5 space-y-4">
                <!-- Progress Bar -->
                <div>
                    <div class="flex items-center justify-between text-xs text-slate-400 mb-1.5">
                        <span>Completed Lessons</span>
                        <span class="font-semibold text-white">{{ $progress['completed'] }} of {{ $progress['total'] }} published</span>
                    </div>
                    <div class="h-2.5 w-full rounded-full bg-slate-800 overflow-hidden">
                        <div class="h-full rounded-full {{ $progress['is_completed'] ? 'bg-emerald-500' : 'bg-amber-500' }}"
                             style="width: {{ $progress['percentage'] }}%"></div>
                    </div>
                </div>

                <div class="rounded-xl bg-slate-950/60 border border-slate-800/80 p-3.5 grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <p class="text-[11px] text-slate-500">Progress Status</p>
                        <p class="font-bold text-white mt-0.5">
                            {{ $progress['is_completed'] ? 'Fully Completed' : ($progress['percentage'] > 0 ? 'In Progress' : 'Not Started') }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px] text-slate-500">Remaining Lessons</p>
                        <p class="font-bold text-white mt-0.5">
                            {{ max(0, $progress['total'] - $progress['completed']) }} lessons
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Certificate Status Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="pb-4 border-b border-slate-800/80">
                    <h2 class="text-base font-bold text-white tracking-tight">Certificate & Credentials</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Permanently retained credentials (even if access expires)</p>
                </div>

                <div class="mt-4 rounded-xl border border-slate-800 bg-slate-950/60 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Certificate Status</span>
                        @if($enrollment->certificate)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                Issued
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium text-slate-400 bg-slate-800">
                                Not Issued
                            </span>
                        @endif
                    </div>

                    @if($enrollment->certificate)
                        <div class="mt-3 flex items-center justify-between">
                            <div>
                                <p class="font-mono font-bold text-xs text-amber-400">{{ $enrollment->certificate->certificate_number }}</p>
                                <p class="text-[10px] text-slate-500 mt-0.5">
                                    Issued on {{ $enrollment->certificate->issued_at?->format('M d, Y') ?? $enrollment->certificate->created_at?->format('M d, Y') }}
                                </p>
                            </div>
                            <a href="{{ route('student.certificates.show', $enrollment->certificate) }}"
                               target="_blank"
                               class="text-xs font-semibold text-amber-400 hover:underline">
                                View Certificate &rarr;
                            </a>
                        </div>
                    @else
                        <p class="mt-2 text-[11px] text-slate-500">
                            Certificates are automatically awarded upon 100% curriculum completion and remain permanently verifiable regardless of access expiration.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Period Course Access History Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Access Period History</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Immutable sequence of access intervals granted for this enrollment.
                </p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 text-xs font-bold text-slate-300">
                {{ $enrollment->accessPeriods->count() }} {{ \Illuminate\Support\Str::plural('Period', $enrollment->accessPeriods->count()) }}
            </span>
        </div>

        @if($enrollment->accessPeriods->isEmpty())
            <div class="py-12 text-center text-xs text-slate-400">
                @if($enrollment->isLegacyLifetimeAccess())
                    <p class="font-semibold text-indigo-300">Legacy Lifetime Enrollment</p>
                    <p class="text-slate-500 mt-1">No discrete periods recorded. Access is permanent and indefinite.</p>
                @else
                    <p>No historical access period records exist for this enrollment.</p>
                @endif
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                            <th class="py-3.5 pl-6 pr-4">Type</th>
                            <th class="py-3.5 px-4">Period Window</th>
                            <th class="py-3.5 px-4">Duration</th>
                            <th class="py-3.5 px-4">Associated Order</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 pr-6 text-right">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($enrollment->accessPeriods as $period)
                            @php
                                $isCurrent = now()->between($period->starts_at, $period->expires_at);
                                $isPast = now()->isAfter($period->expires_at);
                                $typeBadge = match($period->period_type) {
                                    'initial' => 'bg-blue-500/10 text-blue-400 border border-blue-500/30',
                                    'renewal' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                                    'admin_grant' => 'bg-amber-500/10 text-amber-400 border border-amber-500/30',
                                    default => 'bg-slate-800 text-slate-300',
                                };
                                $durationDays = $period->starts_at && $period->expires_at ? round($period->starts_at->diffInDays($period->expires_at)) : null;
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 pl-6 pr-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $typeBadge }}">
                                        {{ str_replace('_', ' ', $period->period_type) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-300">
                                    <div class="font-medium">
                                        {{ $period->starts_at?->format('M d, Y') }} &rarr; {{ $period->expires_at?->format('M d, Y') }}
                                    </div>
                                    <div class="text-[10px] text-slate-500">
                                        {{ $period->starts_at?->format('h:i A') }} to {{ $period->expires_at?->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 font-semibold text-white">
                                    {{ $durationDays ? "{$durationDays} days" : 'N/A' }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($period->order)
                                        <a href="{{ route('admin.orders.show', $period->order) }}"
                                           class="font-mono text-xs font-bold text-amber-400 hover:underline">
                                            {{ $period->order->order_number }}
                                        </a>
                                    @elseif($period->isAdminGrant())
                                        <span class="text-[11px] text-slate-400 italic">Admin Grant</span>
                                    @else
                                        <span class="text-[11px] text-slate-500">Direct / Free</span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4">
                                    @if($isCurrent)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                            Current
                                        </span>
                                    @elseif($isPast)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium text-slate-400 bg-slate-800">
                                            Completed
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-medium text-blue-400 bg-blue-500/10 border border-blue-500/30">
                                            Future
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-6 text-right text-slate-400 text-[11px]">
                                    {{ $period->created_at?->format('M d, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Related Order Transactions Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Order & Payment Context</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Financial orders linked to this student and course. Sensitive credentials and secrets are strictly redacted.
                </p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 text-xs font-bold text-slate-300">
                {{ $allOrders->count() }} {{ \Illuminate\Support\Str::plural('Order', $allOrders->count()) }}
            </span>
        </div>

        @if($allOrders->isEmpty())
            <div class="py-12 text-center text-xs text-slate-400">
                <p>No financial orders exist for this enrollment.</p>
                <p class="text-slate-500 mt-1">Student enrolled via free tier or direct administrative grant.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                            <th class="py-3.5 pl-6 pr-4">Order Number</th>
                            <th class="py-3.5 px-4">Type</th>
                            <th class="py-3.5 px-4">Amount</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4">Date</th>
                            <th class="py-3.5 pr-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($allOrders as $ord)
                            @php
                                $ordStatus = $ord->status?->value ?? $ord->status;
                                $ordBadge = match($ordStatus) {
                                    'paid' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                                    'pending' => 'bg-amber-500/10 text-amber-400 border border-amber-500/30',
                                    'failed' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                                    default => 'bg-slate-800 text-slate-300',
                                };
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 pl-6 pr-4">
                                    <span class="font-mono font-bold text-white">{{ $ord->order_number }}</span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $ord->order_type === 'renewal' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/30' : 'bg-blue-500/10 text-blue-400 border border-blue-500/30' }}">
                                        {{ ucfirst($ord->order_type ?? 'initial') }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 font-bold text-slate-200">
                                    {{ $ord->formattedAmount() }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $ordBadge }}">
                                        {{ ucfirst($ordStatus) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400">
                                    {{ $ord->created_at?->format('M d, Y h:i A') }}
                                </td>
                                <td class="py-3.5 pr-6 text-right">
                                    <a href="{{ route('admin.orders.show', $ord) }}"
                                       class="inline-flex items-center gap-1 text-xs font-semibold text-amber-400 hover:text-amber-300">
                                        Audit Order &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Administrative Audit Trail Table -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Audit Trail & Governance Logs</h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Immutable history of administrative actions, access extensions, and justifications.
                </p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-800 text-xs font-bold text-slate-300">
                {{ $auditLogs->count() }} {{ \Illuminate\Support\Str::plural('Log', $auditLogs->count()) }}
            </span>
        </div>

        @if($auditLogs->isEmpty())
            <div class="py-12 text-center text-xs text-slate-500">
                No administrative audit logs recorded for this enrollment yet.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400 bg-slate-950/40">
                            <th class="py-3.5 pl-6 pr-4">Timestamp</th>
                            <th class="py-3.5 px-4">Actor</th>
                            <th class="py-3.5 px-4">Action</th>
                            <th class="py-3.5 px-4">Description / Reason</th>
                            <th class="py-3.5 pr-6 text-right">IP Address</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($auditLogs as $log)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 pl-6 pr-4 text-slate-300 font-mono text-[11px]">
                                    {{ $log->created_at?->format('M d, Y H:i:s') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-bold text-white block">
                                        {{ $log->admin_name ?? $log->user?->name ?? 'System' }}
                                    </span>
                                    <span class="text-[10px] text-slate-500">
                                        {{ $log->user?->email }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-800 text-slate-300">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-300 max-w-md">
                                    <p class="font-medium text-slate-200">{{ $log->description }}</p>
                                    @if(!empty($log->new_values['reason']))
                                        <p class="text-[11px] text-amber-400/90 mt-0.5 italic">
                                            "{{ $log->new_values['reason'] }}"
                                        </p>
                                    @endif
                                    @if(!empty($log->new_values['expires_at']))
                                        <p class="text-[10px] text-slate-500 mt-0.5">
                                            New Expiration: {{ \Carbon\Carbon::parse($log->new_values['expires_at'])->format('M d, Y H:i') }}
                                        </p>
                                    @endif
                                </td>
                                <td class="py-3.5 pr-6 text-right text-slate-500 font-mono text-[10px]">
                                    {{ $log->ip_address ?? '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection