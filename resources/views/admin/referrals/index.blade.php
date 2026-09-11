@extends('layouts.admin', ['title' => 'Referrals Management - Admin'])

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Referral Program Management</h1>
            <p class="text-sm text-slate-500 mt-1">
                Monitor student referral attributions, conversion performance, and organic growth.
            </p>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Attributions</span>
            <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $totalCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Students registered via referral</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Converted</span>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ $convertedCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Completed paid course enrollment</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Conversion Rate</span>
            <p class="mt-2 text-3xl font-extrabold text-indigo-600">{{ $conversionRate }}%</p>
            <p class="mt-1 text-xs text-slate-500">Enrollment conversion efficiency</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Referrers</span>
            <p class="mt-2 text-3xl font-extrabold text-amber-600">{{ $uniqueReferrersCount }}</p>
            <p class="mt-1 text-xs text-slate-500">Students actively inviting peers</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-xs">
        <form method="GET" action="{{ route('admin.referrals.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search by code, referrer name/email, or student name/email..."
                    class="w-full rounded-lg border border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 shadow-xs focus:outline-none focus:ring-2"
                />
            </div>

            <div class="sm:w-48">
                <select
                    name="status"
                    class="w-full rounded-lg border border-slate-300 focus:border-indigo-600 focus:ring-indigo-600 px-3.5 py-2 text-sm text-slate-900 shadow-xs focus:outline-none focus:ring-2"
                >
                    <option value="">All Statuses</option>
                    <option value="registered" {{ request('status') === 'registered' ? 'selected' : '' }}>Registered</option>
                    <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button
                    type="submit"
                    class="px-4 py-2 rounded-lg bg-slate-900 text-white font-semibold text-sm hover:bg-slate-800 transition shadow-xs"
                >
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status']))
                    <a
                        href="{{ route('admin.referrals.index') }}"
                        class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 font-semibold text-sm hover:bg-slate-50 transition"
                    >
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Referrals Table -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        @if($referrals->isEmpty())
            <div class="py-12 px-4 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-800">No referral attributions found</h4>
                <p class="text-xs text-slate-500 mt-1">
                    {{ request()->hasAny(['search', 'status']) ? 'Try adjusting your search criteria or filters.' : 'Referral records will appear here as students share their referral links.' }}
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">ID</th>
                            <th class="px-6 py-3.5">Referrer</th>
                            <th class="px-6 py-3.5">Referred Student</th>
                            <th class="px-6 py-3.5">Code</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Converted Course</th>
                            <th class="px-6 py-3.5">Attributed At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($referrals as $ref)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 text-xs font-mono text-slate-400">
                                    #{{ $ref->id }}
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $ref->referrer?->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-slate-500">{{ $ref->referrer?->email ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-slate-900">{{ $ref->referred?->name ?? 'Unknown' }}</div>
                                    <div class="text-xs text-slate-500">{{ $ref->referred?->email ?? '—' }}</div>
                                </td>
                                <td class="px-6 py-4 text-xs font-mono font-bold text-slate-800">
                                    {{ $ref->referral_code }}
                                </td>
                                <td class="px-6 py-4">
                                    @php
                                        $statusEnum = $ref->status instanceof \App\Enums\ReferralStatus ? $ref->status : \App\Enums\ReferralStatus::tryFrom($ref->status);
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $statusEnum ? $statusEnum->badgeClasses() : 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                        {{ $statusEnum ? $statusEnum->label() : ucfirst($ref->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-xs">
                                    @if($ref->order && $ref->order->course)
                                        <span class="font-medium text-slate-900">{{ $ref->order->course->title }}</span>
                                        <div class="text-slate-500">Order #{{ $ref->order->id }}</div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    <div>{{ $ref->created_at->format('M d, Y') }}</div>
                                    @if($ref->converted_at)
                                        <div class="text-emerald-600 font-medium">Conv: {{ $ref->converted_at->format('M d, Y') }}</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($referrals->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $referrals->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection