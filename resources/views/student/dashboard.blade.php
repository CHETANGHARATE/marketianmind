@extends('layouts.student')

@section('subcontent')
<div class="space-y-8">
    <!-- 1. Welcome Header Section -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 p-8 sm:p-10 text-white shadow-sm border border-slate-800">
        <div class="relative z-10 max-w-2xl">
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-500/20 px-3.5 py-1 text-xs font-semibold text-indigo-300 border border-indigo-500/30 mb-4">
                <span class="h-1.5 w-1.5 rounded-full bg-indigo-400"></span>
                Student Portal &bull; Marketian Mind
            </div>

            <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white">
                Welcome back, {{ $user->name }}!
            </h1>

            <p class="mt-3 text-sm sm:text-base text-slate-300 leading-relaxed">
                Continue your learning journey and keep building practical marketing skills to grow your business online without depending on expensive agencies.
            </p>

            <div class="mt-6 flex flex-wrap items-center gap-3">
                @if($continueLearningCourse)
                    <a href="{{ $continueLearningCourse['actionUrl'] }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                        {{ $continueLearningCourse['actionLabel'] }} &rarr;
                    </a>
                @else
                    <a href="{{ route('courses') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                        Explore Courses &rarr;
                    </a>
                @endif
                <a href="{{ route('student.courses') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    My Courses
                </a>
                <a href="{{ route('student.orders.index') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    Purchase History
                </a>
                <a href="{{ route('student.profile') }}" class="inline-flex items-center justify-center rounded-xl bg-white/10 px-4 py-2.5 text-xs font-semibold text-white hover:bg-white/20 border border-white/10 transition">
                    Account Settings
                </a>
            </div>
        </div>

        <!-- Decorative background glow -->
        <div class="absolute -right-20 -bottom-20 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- 2. Statistics Overview Grid (4 Cards) -->
    <div>
        <h2 class="text-base font-bold text-slate-900 mb-4">
            Learning Statistics
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- 1. Enrolled Courses -->
            <x-student.stat-card
                title="Enrolled Courses"
                :value="$stats['enrolled_courses']"
                description="Active courses in your library"
                color="indigo"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>

            <!-- 2. In Progress -->
            <x-student.stat-card
                title="In Progress"
                :value="$stats['in_progress']"
                description="Courses actively advancing"
                color="sky"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>

            <!-- 3. Completed -->
            <x-student.stat-card
                title="Completed"
                :value="$stats['completed']"
                description="Finished curriculum tracks"
                color="emerald"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>

            <!-- 4. Overall Progress -->
            <x-student.stat-card
                title="Overall Progress"
                :value="$stats['overall_progress'] . '%'"
                description="Completed practical lessons"
                color="amber"
            >
                <x-slot:icon>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </x-slot:icon>
            </x-student.stat-card>
        </div>
    </div>

    <!-- 3. Continue Learning (Prominent Feature Card) -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Continue Learning
                </h2>
                <p class="text-xs text-slate-500">
                    Pick up where you left off in your active course
                </p>
            </div>
            @if($stats['enrolled_courses'] > 0)
                <a href="{{ route('student.courses') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                    View all enrolled courses &rarr;
                </a>
            @endif
        </div>

        @if ($continueLearningCourse)
            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 sm:p-8 shadow-sm hover:border-indigo-200 transition">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
                        @if($continueLearningCourse['thumbnail'])
                            <img src="{{ $continueLearningCourse['thumbnail'] }}" alt="{{ $continueLearningCourse['title'] }}" class="h-20 w-32 rounded-xl object-cover border border-slate-100 bg-slate-100 shrink-0">
                        @else
                            <div class="h-20 w-32 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 flex items-center justify-center text-white font-black text-xl shadow-xs shrink-0">
                                MM
                            </div>
                        @endif

                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($continueLearningCourse['category'])
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-indigo-700 border border-indigo-100">
                                        {{ $continueLearningCourse['category'] }}
                                    </span>
                                @endif
                                <span class="text-xs text-slate-400">
                                    {{ $continueLearningCourse['modules_count'] }} {{ \Illuminate\Support\Str::plural('Module', $continueLearningCourse['modules_count']) }} &bull; {{ $continueLearningCourse['duration'] }}
                                </span>
                            </div>

                            <h3 class="text-lg font-bold text-slate-900 leading-snug">
                                {{ $continueLearningCourse['title'] }}
                            </h3>

                            @if($continueLearningCourse['next_lesson'])
                                <p class="text-xs font-medium text-indigo-600 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-indigo-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Next Lesson: {{ $continueLearningCourse['next_lesson']->title }}</span>
                                </p>
                            @else
                                <p class="text-xs text-slate-500">
                                    Course completed &bull; Review lessons at any time.
                                </p>
                            @endif

                            <!-- Progress Bar -->
                            <div class="pt-1 max-w-md">
                                <div class="flex items-center justify-between text-[11px] font-semibold text-slate-600 mb-1">
                                    <span>Progress</span>
                                    <span>{{ $continueLearningCourse['progress']['percentage'] }}% ({{ $continueLearningCourse['progress']['completed'] }}/{{ $continueLearningCourse['progress']['total'] }} lessons)</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: {{ $continueLearningCourse['progress']['percentage'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2.5 shrink-0">
                        @if($continueLearningCourse['is_completed'] && !empty($continueLearningCourse['certificate']))
                            <a href="{{ route('student.certificates.show', $continueLearningCourse['certificate']) }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white shadow-xs hover:bg-emerald-500 transition w-full sm:w-auto">
                                View Certificate &rarr;
                            </a>
                        @endif
                        <a href="{{ $continueLearningCourse['actionUrl'] }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-6 py-3 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition w-full sm:w-auto">
                            {{ $continueLearningCourse['actionLabel'] }} &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @else
            <x-student.empty-state
                title="You're ready to start learning"
                description="You haven't enrolled in any courses yet. Explore Marketian Mind courses to start acquiring practical marketing skills for your business."
                :actionUrl="route('courses')"
                actionLabel="Explore Courses"
            >
                <svg class="w-8 h-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
            </x-student.empty-state>
        @endif
    </div>

    <!-- 4. Enrolled Courses Section ("My Courses") -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    My Courses
                </h2>
                <p class="text-xs text-slate-500">
                    All courses you are currently enrolled in
                </p>
            </div>
            @if(count($enrolledCourses) > 0)
                <span class="text-xs font-semibold text-slate-500">
                    {{ count($enrolledCourses) }} {{ \Illuminate\Support\Str::plural('Course', count($enrolledCourses)) }}
                </span>
            @endif
        </div>

        @if(count($enrolledCourses) > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($enrolledCourses as $course)
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200/90 bg-white shadow-sm hover:shadow-md transition overflow-hidden">
                        <!-- Card Top Thumbnail -->
                        <div class="relative aspect-video w-full bg-slate-100 overflow-hidden">
                            @if($course['thumbnail'])
                                <img src="{{ $course['thumbnail'] }}" alt="{{ $course['title'] }}" class="h-full w-full object-cover">
                            @else
                                <div class="h-full w-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-indigo-800 flex items-center justify-center text-white font-black text-2xl">
                                    MM
                                </div>
                            @endif

                            <!-- Status Pill on Top -->
                            <div class="absolute top-3 right-3">
                                @if($course['is_completed'])
                                    <span class="inline-flex items-center rounded-md bg-emerald-500/90 backdrop-blur-xs px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs">
                                        Completed
                                    </span>
                                @elseif($course['progress']['percentage'] > 0)
                                    <span class="inline-flex items-center rounded-md bg-indigo-600/90 backdrop-blur-xs px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs">
                                        In Progress
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-slate-800/80 backdrop-blur-xs px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-white shadow-xs">
                                        Not Started
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 flex-1 flex flex-col justify-between space-y-4">
                            <div>
                                @if($course['category'])
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 block mb-1">
                                        {{ $course['category'] }}
                                    </span>
                                @endif
                                <h3 class="font-bold text-slate-900 text-sm line-clamp-2 leading-snug">
                                    {{ $course['title'] }}
                                </h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Instructor: {{ $course['instructor'] }}
                                </p>
                            </div>

                            <!-- Progress Indicator -->
                            <div class="space-y-1.5 pt-2 border-t border-slate-100">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-slate-500 font-medium">Completion</span>
                                    <span class="font-bold text-slate-800">{{ $course['progress']['percentage'] }}%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-300" style="width: {{ $course['progress']['percentage'] }}%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer Action -->
                        <div class="px-5 pb-5 pt-1 space-y-2">
                            @if($course['is_completed'] && !empty($course['certificate']))
                                <a href="{{ route('student.certificates.show', $course['certificate']) }}" class="w-full inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-emerald-500 shadow-2xs transition">
                                    View Certificate &rarr;
                                </a>
                            @endif
                            <a href="{{ $course['actionUrl'] }}" class="w-full inline-flex items-center justify-center rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-indigo-600 hover:text-white hover:border-indigo-600 shadow-2xs transition">
                                {{ $course['actionLabel'] }} &rarr;
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-white p-8 text-center">
                <p class="text-sm font-semibold text-slate-700">You haven't enrolled in any courses yet.</p>
                <p class="text-xs text-slate-400 mt-1">Enroll in practical marketing courses designed to help you generate customer growth.</p>
                <div class="mt-4">
                    <a href="{{ route('courses') }}" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-500 transition">
                        Explore Courses &rarr;
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- 5. Two-Column Grid: Recent Orders + Quick Account Navigation -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 pt-2">
        <!-- Recent Orders (2 Columns on large screens) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">
                        Recent Purchases
                    </h2>
                    <p class="text-xs text-slate-500">
                        Your latest course transactions and order receipts
                    </p>
                </div>
                <a href="{{ route('student.orders.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 transition">
                    View all orders &rarr;
                </a>
            </div>

            <div class="rounded-2xl border border-slate-200/90 bg-white shadow-sm overflow-hidden">
                @if($recentOrders->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <tr>
                                    <th class="px-5 py-3.5">Order #</th>
                                    <th class="px-5 py-3.5">Course</th>
                                    <th class="px-5 py-3.5">Amount</th>
                                    <th class="px-5 py-3.5">Status</th>
                                    <th class="px-5 py-3.5 text-right">Receipt</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($recentOrders as $order)
                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="px-5 py-3.5 font-mono font-bold text-slate-900">
                                            {{ $order->order_number }}
                                        </td>
                                        <td class="px-5 py-3.5 font-semibold text-slate-900">
                                            {{ $order->course->title }}
                                        </td>
                                        <td class="px-5 py-3.5 font-bold text-slate-900">
                                            {{ $order->formattedAmount() }}
                                        </td>
                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $order->status->badgeClasses() }}">
                                                {{ $order->status->label() }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right">
                                            <a href="{{ route('student.orders.show', $order) }}" class="rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:border-slate-300 transition">
                                                Receipt
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-8 text-center">
                        <p class="text-xs font-semibold text-slate-600">No orders yet.</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">When you purchase course certifications, your invoices will appear here.</p>
                        <div class="mt-3">
                            <a href="{{ route('courses') }}" class="text-xs font-bold text-indigo-600 hover:text-indigo-500">
                                Browse Paid Courses &rarr;
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Quick Account Settings & Support Card (1 Column on large screens) -->
        <div class="space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">
                    Account &amp; Support
                </h2>
                <p class="text-xs text-slate-500">
                    Manage your credentials and access support
                </p>
            </div>

            <div class="rounded-2xl border border-slate-200/90 bg-white p-6 shadow-sm space-y-5">
                <!-- User summary -->
                <div class="flex items-center gap-3.5 pb-4 border-b border-slate-100">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-white font-black text-base shadow-xs shrink-0">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <p class="font-bold text-slate-900 text-sm truncate">{{ $user->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $user->email }}</p>
                        <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[10px] font-bold text-indigo-700 mt-1">
                            {{ ucfirst($user->role->value ?? 'Student') }} Account
                        </span>
                    </div>
                </div>

                <!-- Shortcuts -->
                <div class="space-y-2">
                    <a href="{{ route('student.profile') }}" class="flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition border border-transparent hover:border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span>Profile Information</span>
                        </div>
                        <span class="text-slate-400">&rsaquo;</span>
                    </a>

                    <a href="{{ route('student.profile') }}#password-settings" class="flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition border border-transparent hover:border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            <span>Change Password</span>
                        </div>
                        <span class="text-slate-400">&rsaquo;</span>
                    </a>

                    <a href="{{ route('student.orders.index') }}" class="flex items-center justify-between p-2.5 rounded-xl text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-indigo-600 transition border border-transparent hover:border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                            </svg>
                            <span>Purchase History</span>
                        </div>
                        <span class="text-slate-400">&rsaquo;</span>
                    </a>
                </div>

                <!-- Learning Support Notice -->
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 text-[11px] text-slate-500">
                    <p class="font-bold text-slate-800">Need Help or Guidance?</p>
                    <p class="mt-0.5">Reach out to our curriculum mentors at <a href="mailto:support@marketianmind.com" class="text-indigo-600 font-medium hover:underline">support@marketianmind.com</a>.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection