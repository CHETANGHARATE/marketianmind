@extends('layouts.student')

@section('subcontent')
<div class="space-y-10">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                My Courses &amp; Access
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Track your course access validity, review curriculum progress, and manage manual renewals.
            </p>
        </div>
        <a href="{{ route('courses') }}" class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
            Browse Course Catalog &rarr;
        </a>
    </div>

    <!-- Filter Tabs -->
    @if(isset($filterCounts) && $filterCounts['all'] > 0)
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
            <a href="{{ route('student.courses', ['status' => 'all']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentFilter === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                All Courses
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentFilter === 'all' ? 'bg-indigo-700 text-white' : 'bg-slate-200 text-slate-700' }}">
                    {{ $filterCounts['all'] }}
                </span>
            </a>

            <a href="{{ route('student.courses', ['status' => 'active']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentFilter === 'active' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Active
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentFilter === 'active' ? 'bg-indigo-700 text-white' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $filterCounts['active'] }}
                </span>
            </a>

            <a href="{{ route('student.courses', ['status' => 'expiring']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentFilter === 'expiring' ? 'bg-amber-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Expiring Soon
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentFilter === 'expiring' ? 'bg-amber-700 text-white' : 'bg-amber-100 text-amber-700' }}">
                    {{ $filterCounts['expiring'] }}
                </span>
            </a>

            <a href="{{ route('student.courses', ['status' => 'expired']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentFilter === 'expired' ? 'bg-rose-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Expired
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentFilter === 'expired' ? 'bg-rose-700 text-white' : 'bg-rose-100 text-rose-700' }}">
                    {{ $filterCounts['expired'] }}
                </span>
            </a>

            <a href="{{ route('student.courses', ['status' => 'completed']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition {{ $currentFilter === 'completed' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                Completed
                <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentFilter === 'completed' ? 'bg-emerald-700 text-white' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $filterCounts['completed'] }}
                </span>
            </a>
        </div>
    @endif

    <!-- Enrolled Courses Section -->
    @if(isset($enrolledCourses) && count($enrolledCourses) > 0)
        <div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($enrolledCourses as $course)
                    <div class="flex flex-col rounded-2xl border {{ $course['access_state'] === 'expired' ? 'border-rose-200 bg-rose-50/10' : ($course['access_state'] === 'expiring' ? 'border-amber-200 bg-amber-50/10' : 'border-slate-200 bg-white') }} overflow-hidden shadow-sm hover:shadow-md transition">
                        <!-- Thumbnail -->
                        <div class="relative aspect-video bg-slate-100 overflow-hidden">
                            @if(!empty($course['thumbnail']))
                                <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-gradient-to-br from-indigo-500/10 via-purple-500/5 to-slate-100 flex items-center justify-center p-6 text-center">
                                    <span class="text-xs font-semibold text-slate-400">{{ $course['title'] }}</span>
                                </div>
                            @endif

                            <div class="absolute top-3 left-3 flex flex-col gap-1.5">
                                <x-student.access-badge :state="$course['access_state']" :label="$course['badge_details']['label']" />
                            </div>

                            <div class="absolute top-3 right-3 flex items-center gap-1.5">
                                @if($course['is_completed'])
                                    <span class="inline-flex items-center rounded-full bg-emerald-600/95 backdrop-blur-xs px-2.5 py-0.5 text-xs font-bold text-white shadow-sm">
                                        Completed ✓
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="flex flex-1 flex-col p-5">
                            <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                                @if(!empty($course['category']))
                                    <span class="font-semibold text-indigo-600">{{ $course['category'] }}</span>
                                    <span>&bull;</span>
                                @endif
                                <span>{{ $course['modules'] }} {{ Str::plural('Module', $course['modules']) }}</span>
                                @if(!empty($course['duration']))
                                    <span>&bull;</span>
                                    <span>{{ $course['duration'] }}</span>
                                @endif
                            </div>

                            <h3 class="text-base font-bold text-slate-900 line-clamp-2 mb-1.5">
                                {{ $course['title'] }}
                            </h3>

                            <p class="text-xs text-slate-500 line-clamp-2 mb-4">
                                {{ $course['description'] }}
                            </p>

                            <!-- Access Validity Strip -->
                            <div class="rounded-xl p-3 mb-4 {{ $course['access_state'] === 'expired' ? 'bg-rose-50 border border-rose-200' : ($course['access_state'] === 'expiring' ? 'bg-amber-50 border border-amber-200' : 'bg-slate-50 border border-slate-100') }}">
                                <div class="flex items-center justify-between text-xs">
                                    @if($course['access_state'] === 'lifetime')
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                            <span class="font-bold text-emerald-800">Lifetime Access</span>
                                        </div>
                                        <span class="text-[11px] text-emerald-700">No expiration</span>
                                    @elseif($course['access_state'] === 'expired')
                                        <div>
                                            <span class="font-bold text-rose-800 block text-xs">Access Expired</span>
                                            @if($course['formatted_expiry'])
                                                <span class="text-[11px] text-rose-600">Expired on {{ $course['formatted_expiry'] }}</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] font-bold text-rose-700">Manual Renewal</span>
                                    @elseif($course['access_state'] === 'expiring')
                                        <div>
                                            <span class="font-bold text-amber-800 block text-xs">Expiring Soon</span>
                                            @if($course['formatted_expiry'])
                                                <span class="text-[11px] text-amber-600">Expires {{ $course['formatted_expiry'] }}</span>
                                            @endif
                                        </div>
                                        <span class="inline-flex items-center rounded-md bg-amber-200/80 px-2 py-0.5 text-[11px] font-bold text-amber-800">
                                            {{ $course['remaining_days_text'] }}
                                        </span>
                                    @else
                                        <div>
                                            <span class="font-bold text-slate-800 block text-xs">Active Access</span>
                                            @if($course['formatted_expiry'])
                                                <span class="text-[11px] text-slate-500">Expires {{ $course['formatted_expiry'] }}</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] font-semibold text-slate-600">
                                            {{ $course['remaining_days_text'] }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Progress Bar -->
                            <div class="pt-2 border-t border-slate-100 mb-4 mt-auto">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-semibold text-slate-700">{{ $course['progress']['percentage'] }}% Complete</span>
                                    <span class="text-slate-400">
                                        {{ $course['progress']['completed'] }} of {{ $course['progress']['total'] }} lessons
                                    </span>
                                </div>
                                <div class="h-2 w-full rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full {{ $course['access_state'] === 'expired' ? 'bg-slate-400' : 'bg-indigo-600' }} transition-all duration-300" style="width: {{ $course['progress']['percentage'] }}%"></div>
                                </div>
                                @if($course['access_state'] === 'expired')
                                    <p class="text-[10px] text-slate-500 mt-1.5">
                                        ✓ Your {{ $course['progress']['percentage'] }}% course progress is permanently preserved.
                                    </p>
                                @endif
                            </div>

                            <!-- CTA Buttons -->
                            <div class="space-y-2">
                                @if($course['is_completed'] && !empty($course['certificate']))
                                    <a href="{{ route('student.certificates.show', $course['certificate']) }}" class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-500 transition">
                                        View Certificate &rarr;
                                    </a>
                                @endif

                                @if($course['access_state'] === 'expired')
                                    <form action="{{ route('student.courses.purchase', $course['model'] ?? $course['course']) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg bg-rose-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-rose-500 transition cursor-pointer">
                                            {{ $course['renewal_label'] }} &rarr;
                                        </button>
                                    </form>
                                @else
                                    <div class="flex items-center gap-2">
                                        @if($course['can_renew'])
                                            <form action="{{ route('student.courses.purchase', $course['model'] ?? $course['course']) }}" method="POST" class="shrink-0">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-amber-500 hover:bg-amber-600 px-3 py-2.5 text-xs font-bold text-white shadow-sm transition cursor-pointer">
                                                    Renew Early
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ $course['actionUrl'] }}" class="inline-flex flex-1 items-center justify-center rounded-lg bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-indigo-500 transition">
                                            {{ $course['actionLabel'] }} &rarr;
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @elseif(isset($filterCounts) && $filterCounts['all'] > 0)
        <!-- Empty filter state -->
        <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center space-y-3">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </div>
            <h3 class="text-sm font-bold text-slate-900">No courses match this filter</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">
                You do not have any courses with the status "{{ ucfirst($currentFilter) }}".
            </p>
            <div>
                <a href="{{ route('student.courses', ['status' => 'all']) }}" class="inline-flex items-center rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 text-xs font-semibold transition">
                    View All Enrolled Courses &rarr;
                </a>
            </div>
        </div>
    @endif

    <!-- Browse Courses Section -->
    <div class="pt-6 border-t border-slate-200">
        <div class="mb-6">
            <h2 class="text-xl font-bold tracking-tight text-slate-900">
                Available Courses
            </h2>
            <p class="mt-1 text-xs text-slate-500">
                Explore our full curriculum of self-paced marketing training for business owners.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <x-student.course-card
                title="Digital Marketing for Business Owners"
                description="A practical, non-agency framework to understand digital channels, customer acquisition, and marketing funnels without agency fees."
                :modules="6"
                duration="4 Weeks"
                level="Beginner to Intermediate"
                badge="Featured Course"
                :actionUrl="route('course.details')"
                actionLabel="Course Curriculum & Preview"
            />
        </div>
    </div>
</div>
@endsection