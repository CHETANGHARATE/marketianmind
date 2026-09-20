@extends('layouts.admin')

@section('subcontent')
<div class="space-y-8">
    <!-- Breadcrumb & Back Action -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.students.index') }}"
           class="inline-flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-amber-400 transition">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Students Directory
        </a>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.orders.index', ['search' => $student->email]) }}"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                Audit Orders
            </a>
        </div>
    </div>

    <!-- Student Profile Header Card -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div class="flex items-center gap-4">
                <div class="h-16 w-16 rounded-2xl bg-amber-500/20 text-amber-400 font-black text-xl flex items-center justify-center shrink-0 border border-amber-500/30 shadow-inner">
                    {{ strtoupper(substr($student->name, 0, 1)) }}
                </div>
                <div>
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <h1 class="text-xl sm:text-2xl font-bold tracking-tight text-white">
                            {{ $student->name }}
                        </h1>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $lifecycleStage->badgeClasses() }}" title="{{ $lifecycleStage->description() }}">
                            {{ $lifecycleStage->label() }}
                        </span>
                        @if($student->email_verified_at)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">
                                Email Verified
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-500/10 text-slate-400 border border-slate-500/30">
                                Unverified Email
                            </span>
                        @endif
                    </div>
                    <p class="mt-1 text-xs text-slate-400">
                        {{ $student->email }} &bull; Registered on {{ $student->created_at?->format('F d, Y') }} ({{ $student->created_at?->diffForHumans() }})
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3 sm:border-l sm:border-slate-800 sm:pl-6">
                <div class="text-left sm:text-right">
                    <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Lifetime Investment</p>
                    <p class="text-xl font-black text-white mt-0.5">{{ $stats['formatted_paid_revenue'] }}</p>
                    <p class="text-[10px] text-emerald-400 font-medium">{{ $stats['paid_orders'] }} paid transactions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Lifecycle & Communication Consent Inspector -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Customer Lifecycle Diagnosis Card -->
        <div class="lg:col-span-2 rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </span>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">Customer Lifecycle Status</h2>
                </div>
                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold uppercase tracking-wider border {{ $lifecycleStage->badgeClasses() }}">
                    {{ $lifecycleStage->label() }}
                </span>
            </div>
            <div class="mt-4">
                <p class="text-xs text-slate-300 leading-relaxed font-medium">
                    {{ $lifecycleStage->description() }}
                </p>
                <div class="mt-4 pt-3 border-t border-slate-800/80 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div class="rounded-xl bg-slate-950/60 p-3 border border-slate-800/60">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">Authoritative Source</span>
                        <span class="text-slate-200 font-medium">Derived dynamically from Orders, Access Periods, Lesson Completions & Certificates</span>
                    </div>
                    <div class="rounded-xl bg-slate-950/60 p-3 border border-slate-800/60">
                        <span class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 block mb-1">Business Model Rule</span>
                        <span class="text-slate-200 font-medium">One-time purchase &bull; 365-day access validity &bull; No recurring subscriptions</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Communication Consent & Channels Card -->
        <div class="rounded-2xl border border-slate-800 bg-slate-900/90 p-6 shadow-sm">
            <div class="flex items-center gap-2 pb-3 border-b border-slate-800">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                    </svg>
                </span>
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-200">Consent & Channels</h2>
            </div>
            <div class="mt-4 space-y-3 text-xs">
                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60">
                    <div>
                        <span class="font-semibold text-slate-300 block">Email Channel</span>
                        <span class="text-[10px] text-slate-400">
                            @if($communicationConsent['email_verified'])
                                Verified on {{ $communicationConsent['email_verified_at']?->format('M d, Y') }}
                            @else
                                Verification pending
                            @endif
                        </span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $communicationConsent['email_verified'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30' }}">
                        {{ $communicationConsent['email_verified'] ? 'Verified' : 'Unverified' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60">
                    <div>
                        <span class="font-semibold text-slate-300 block">WhatsApp Channel</span>
                        <span class="text-[10px] text-slate-400">
                            @if($communicationConsent['whatsapp_opt_in'])
                                Opted-in {{ $communicationConsent['whatsapp_opted_in_at'] ? '(' . $communicationConsent['whatsapp_opted_in_at']->format('M d, Y') . ')' : '' }}
                            @else
                                No explicit opt-in
                            @endif
                        </span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $communicationConsent['whatsapp_opt_in'] ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-slate-500/10 text-slate-400 border border-slate-500/30' }}">
                        {{ $communicationConsent['whatsapp_opt_in'] ? 'Opted In' : 'Not Opted In' }}
                    </span>
                </div>

                <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/60">
                    <div>
                        <span class="font-semibold text-slate-300 block">Marketing Opt-Out</span>
                        <span class="text-[10px] text-slate-400">Unsubscribe preference</span>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider {{ $communicationConsent['marketing_unsubscribed'] ? 'bg-rose-500/10 text-rose-400 border border-rose-500/30' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' }}">
                        {{ $communicationConsent['marketing_unsubscribed'] ? 'Unsubscribed' : 'Subscribed' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Summary Statistics Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Total Enrollments</span>
            <div class="mt-2 text-2xl font-black text-white">{{ $stats['total_enrollments'] }}</div>
            <span class="text-[10px] text-slate-500">Enrolled courses</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Completed Courses</span>
            <div class="mt-2 text-2xl font-black text-emerald-400">{{ $stats['completed_courses'] }}</div>
            <span class="text-[10px] text-slate-500">100% completion</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">In-Progress</span>
            <div class="mt-2 text-2xl font-black text-amber-400">{{ $stats['in_progress_courses'] }}</div>
            <span class="text-[10px] text-slate-500">Active learning</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Paid Orders</span>
            <div class="mt-2 text-2xl font-black text-white">{{ $stats['paid_orders'] }}</div>
            <span class="text-[10px] text-slate-500">Completed orders</span>
        </div>
        <div class="rounded-xl border border-slate-800 bg-slate-900/80 p-4 col-span-2 sm:col-span-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Certificates</span>
            <div class="mt-2 text-2xl font-black text-amber-400">{{ $stats['certificates_count'] }}</div>
            <span class="text-[10px] text-slate-500">Earned credentials</span>
        </div>
    </div>

    <!-- Section: Course Enrollments & Learning Progress -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Course Enrollments & Learning Progress</h2>
                <p class="text-xs text-slate-400 mt-0.5">Courses this student is actively enrolled in</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">{{ $stats['total_enrollments'] }} Total</span>
        </div>

        @if($enrollments->isEmpty())
            <div class="py-12 text-center">
                <div class="mx-auto w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300">No course enrollments found</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Student has not joined any courses yet.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="py-3 pr-4">Course</th>
                            <th class="py-3 px-4">Access Lifecycle</th>
                            <th class="py-3 px-4">Enrolled At</th>
                            <th class="py-3 px-4 w-44">Progress</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 pl-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($enrollments as $enrollment)
                            @php
                                $prog = $enrollment->course_progress;
                                $percentage = $prog['percentage'] ?? 0;
                                $isCompleted = ($enrollment->status?->value ?? $enrollment->status) === 'completed' || ($prog['is_completed'] ?? false);
                                $adminBadge = $enrollment->getAdminAccessBadgeDetails();
                            @endphp
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 pr-4">
                                    <span class="font-bold text-white block">
                                        {{ $enrollment->course?->title ?? 'Course Not Available' }}
                                    </span>
                                    <span class="text-[11px] text-slate-400">
                                        {{ $enrollment->course?->category?->name ?? 'Uncategorized' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        @if(isset($courseLifecycleStages[$enrollment->course_id]))
                                            @php $cStage = $courseLifecycleStages[$enrollment->course_id]; @endphp
                                            <div>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider border {{ $cStage->badgeClasses() }}" title="{{ $cStage->description() }}">
                                                    {{ $cStage->label() }}
                                                </span>
                                            </div>
                                        @endif
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $adminBadge['classes'] }}">
                                            {{ $adminBadge['label'] }}
                                        </span>
                                        <div class="text-[11px]">
                                            @if($enrollment->isLegacyLifetimeAccess())
                                                <span class="text-indigo-300">Permanent</span>
                                            @elseif($enrollment->expires_at)
                                                <span class="{{ $enrollment->isAccessExpired() ? 'text-rose-400' : 'text-slate-300' }}">
                                                    {{ $enrollment->expires_at->format('M d, Y') }}
                                                </span>
                                            @else
                                                <span class="text-slate-500">-</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-300">
                                    {{ $enrollment->enrolled_at?->format('M d, Y') ?? $enrollment->created_at?->format('M d, Y') }}
                                </td>
                                <td class="py-3.5 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center justify-between text-[10px]">
                                            <span class="font-bold text-white">{{ $percentage }}%</span>
                                            <span class="text-slate-400">{{ $prog['completed'] ?? 0 }}/{{ $prog['total'] ?? 0 }} lessons</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-slate-800 overflow-hidden">
                                            <div class="h-full rounded-full {{ $isCompleted ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                                 style="width: {{ $percentage }}%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $isCompleted ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-blue-500/10 text-blue-400 border border-blue-500/30' }}">
                                        {{ $isCompleted ? 'Completed' : 'Active' }}
                                    </span>
                                </td>
                                <td class="py-3.5 pl-4 text-right">
                                    <div class="flex items-center justify-end gap-3">
                                        <a href="{{ route('admin.enrollments.show', $enrollment) }}"
                                           class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                                            Manage Access &rarr;
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($enrollments->hasPages())
                <div class="mt-4 pt-4 border-t border-slate-800">
                    {{ $enrollments->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Section: Purchase & Order History -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Purchase & Order History</h2>
                <p class="text-xs text-slate-400 mt-0.5">Financial transactions initiated by this student</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">{{ $student->orders()->count() }} Orders</span>
        </div>

        @if($orders->isEmpty())
            <div class="py-12 text-center">
                <div class="mx-auto w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300">No order records found</p>
                <p class="text-[11px] text-slate-500 mt-0.5">This student has not placed any paid or checkout orders.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="py-3 pr-4">Order #</th>
                            <th class="py-3 px-4">Course</th>
                            <th class="py-3 px-4">Amount</th>
                            <th class="py-3 px-4">Status</th>
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 pl-4 text-right">Audit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($orders as $order)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 pr-4 font-mono font-bold text-white">
                                    {{ $order->order_number }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-200">
                                    {{ $order->course?->title ?? 'Course Not Available' }}
                                </td>
                                <td class="py-3.5 px-4 font-bold text-white">
                                    {{ $order->formattedAmount() }}
                                </td>
                                <td class="py-3.5 px-4">
                                    @php
                                        $status = $order->status;
                                        $badgeClass = match($status?->value ?? $status) {
                                            'paid' => 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30',
                                            'pending' => 'bg-amber-500/10 text-amber-400 border border-amber-500/30',
                                            'failed' => 'bg-rose-500/10 text-rose-400 border border-rose-500/30',
                                            'cancelled' => 'bg-slate-500/10 text-slate-400 border border-slate-500/30',
                                            default => 'bg-slate-500/10 text-slate-300 border border-slate-500/30',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider {{ $badgeClass }}">
                                        {{ ucfirst($status?->value ?? $status) }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-400">
                                    {{ $order->created_at?->format('M d, Y') }}
                                </td>
                                <td class="py-3.5 pl-4 text-right">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="text-xs font-semibold text-amber-400 hover:text-amber-300 transition">
                                        View Order &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($orders->hasPages())
                <div class="mt-4 pt-4 border-t border-slate-800">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Section: Issued Certificates -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Issued Certificates</h2>
                <p class="text-xs text-slate-400 mt-0.5">Accreditation credentials earned by this student</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">{{ $stats['certificates_count'] }} Earned</span>
        </div>

        @if($certificates->isEmpty())
            <div class="py-12 text-center">
                <div class="mx-auto w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300">No certificates earned yet</p>
                <p class="text-[11px] text-slate-500 mt-0.5">Certificates are automatically issued upon 100% course completion.</p>
            </div>
        @else
            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                            <th class="py-3 pr-4">Certificate #</th>
                            <th class="py-3 px-4">Course</th>
                            <th class="py-3 px-4">Issue Date</th>
                            <th class="py-3 pl-4 text-right">View</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($certificates as $cert)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="py-3.5 pr-4 font-mono font-bold text-amber-400">
                                    {{ $cert->certificate_number }}
                                </td>
                                <td class="py-3.5 px-4 text-white font-semibold">
                                    {{ $cert->course_title ?? $cert->course?->title }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-400">
                                    {{ $cert->issued_at?->format('M d, Y') ?? $cert->created_at?->format('M d, Y') }}
                                </td>
                                <td class="py-3.5 pl-4 text-right">
                                    <a href="{{ route('student.certificates.show', $cert) }}"
                                       target="_blank"
                                       class="text-xs font-semibold text-amber-400 hover:underline">
                                        View Certificate &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($certificates->hasPages())
                <div class="mt-4 pt-4 border-t border-slate-800">
                    {{ $certificates->links() }}
                </div>
            @endif
        @endif
    </div>

    <!-- Section: Customer Lifecycle Audit Timeline -->
    <div class="rounded-2xl border border-slate-800 bg-slate-900/90 shadow-sm p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">Customer Lifecycle Audit Timeline</h2>
                <p class="text-xs text-slate-400 mt-0.5">Chronological relationship history derived from authoritative database records</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">{{ $lifecycleTimeline->count() }} Milestones</span>
        </div>

        @if($lifecycleTimeline->isEmpty())
            <div class="py-12 text-center">
                <div class="mx-auto w-10 h-10 rounded-full bg-slate-800 flex items-center justify-center text-slate-400 mb-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <p class="text-xs font-medium text-slate-300">No lifecycle events recorded</p>
            </div>
        @else
            <div class="mt-6 relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-800">
                @foreach($lifecycleTimeline as $event)
                    <div class="relative group">
                        <!-- Timeline bullet -->
                        <div class="absolute -left-6 top-1.5 flex items-center justify-center w-5 h-5 rounded-full bg-slate-950 border-2 border-slate-700 group-hover:border-amber-400 transition">
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-400 group-hover:bg-amber-400 transition"></div>
                        </div>

                        <!-- Milestone content -->
                        <div class="rounded-xl border border-slate-800/80 bg-slate-950/60 p-4 hover:border-slate-700 transition">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-1.5">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-white">{{ $event['title'] }}</span>
                                    @if(!empty($event['badge']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ $event['badge_classes'] ?? 'bg-slate-800 text-slate-300 border border-slate-700' }}">
                                            {{ $event['badge'] }}
                                        </span>
                                    @endif
                                </div>
                                <time class="text-[11px] text-slate-400 font-mono">
                                    {{ $event['timestamp']?->format('M d, Y \a\t h:i A') }} ({{ $event['timestamp']?->diffForHumans() }})
                                </time>
                            </div>
                            <p class="text-xs text-slate-300">
                                {{ $event['description'] }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection