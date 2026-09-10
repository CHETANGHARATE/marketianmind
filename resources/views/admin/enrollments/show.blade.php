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
            Back to Enrollments Directory
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

    <!-- Enrollment Overview Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                        Enrollment Record #{{ $enrollment->id }}
                    </h1>
                    @php
                        $statusVal = $enrollment->status?->value ?? $enrollment->status;
                        $statusBadgeClass = match($statusVal) {
                            'completed' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                            'cancelled' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                            default => 'bg-blue-500/10 text-blue-400 border border-blue-500/30',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold uppercase tracking-wider {{ $statusBadgeClass }}">
                        {{ ucfirst($statusVal) }}
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

            <div class="flex items-center gap-3 sm:border-l sm:border-slate-800 sm:pl-6">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Completion Date</p>
                    <p class="text-sm font-bold text-white mt-0.5">
                        {{ $enrollment->completed_at?->format('M d, Y \a\t h:i A') ?? 'Not Completed' }}
                    </p>
                </div>
            </div>
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
                                <span class="font-bold text-emerald-400 ml-1">Free</span>
                            @else
                                <span class="font-bold text-white ml-1">₹{{ number_format($enrollment->course->effectivePrice(), 2) }}</span>
                            @endif
                        </div>
                        <span class="text-slate-700">&bull;</span>
                        <div>
                            <span class="text-slate-500">Status:</span>
                            <span class="font-semibold text-slate-300 ml-1">{{ ucfirst($enrollment->course->status?->value ?? $enrollment->course->status) }}</span>
                        </div>
                    </div>
                </div>
            @else
                <p class="mt-4 text-xs text-slate-500">Course record is unavailable.</p>
            @endif
        </div>
    </div>

    <!-- 2-Column Grid: Learning Progress & Order / Certificate Relationships -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Learning Progress Breakdown -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
                <div>
                    <h2 class="text-base font-bold text-white tracking-tight">Learning Progress</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Real-time lesson tracking and curriculum completion</p>
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

        <!-- Certificate & Payment Association Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm flex flex-col justify-between">
            <div>
                <div class="pb-4 border-b border-slate-800/80">
                    <h2 class="text-base font-bold text-white tracking-tight">Credentials & Purchase Context</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Associated order transactions and certificates</p>
                </div>

                <div class="mt-4 space-y-4">
                    <!-- Certificate Section -->
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
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
                            <div class="mt-2.5 flex items-center justify-between">
                                <div>
                                    <p class="font-mono font-bold text-xs text-amber-400">{{ $enrollment->certificate->certificate_number }}</p>
                                    <p class="text-[10px] text-slate-500 mt-0.5">
                                        Issued on {{ $enrollment->certificate->issued_at?->format('M d, Y') ?? $enrollment->certificate->created_at?->format('M d, Y') }}
                                    </p>
                                </div>
                                <a href="{{ route('student.certificates.show', $enrollment->certificate) }}"
                                   target="_blank"
                                   class="text-xs font-semibold text-amber-400 hover:underline">
                                    View &rarr;
                                </a>
                            </div>
                        @else
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                Automatically generated when curriculum progress reaches 100%.
                            </p>
                        @endif
                    </div>

                    <!-- Payment / Order Section -->
                    <div class="rounded-xl border border-slate-800 bg-slate-950/60 p-3.5">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Order & Payment Context</span>
                            @if($order)
                                @php
                                    $orderStatus = $order->status?->value ?? $order->status;
                                    $orderBadge = match($orderStatus) {
                                        'paid' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                                        'pending' => 'bg-amber-500/10 text-amber-400 border border-amber-500/30',
                                        'failed' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                                        default => 'bg-slate-800 text-slate-300',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $orderBadge }}">
                                    {{ ucfirst($orderStatus) }}
                                </span>
                            @elseif($enrollment->course?->is_free)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                    Free Access
                                </span>
                            @else
                                <span class="text-[10px] text-slate-500">Direct Access</span>
                            @endif
                        </div>

                        @if($order)
                            <div class="mt-2.5 flex items-center justify-between">
                                <div>
                                    <p class="font-mono font-bold text-xs text-white">{{ $order->order_number }}</p>
                                    <p class="text-[11px] font-semibold text-amber-400 mt-0.5">{{ $order->formattedAmount() }}</p>
                                </div>
                                <a href="{{ route('admin.orders.show', $order) }}"
                                   class="text-xs font-semibold text-amber-400 hover:underline">
                                    Audit Order &rarr;
                                </a>
                            </div>
                        @else
                            <p class="mt-1.5 text-[11px] text-slate-500">
                                {{ $enrollment->course?->is_free ? 'Student enrolled directly into this free course.' : 'Enrolled via platform administrator or direct grant.' }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection