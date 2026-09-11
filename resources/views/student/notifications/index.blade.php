@extends('layouts.student')

@section('subcontent')
<div class="space-y-6">
    <!-- Header & Quick Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Notifications
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Track your course enrollments, payments, completions, and certificate updates.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            @if ($unreadCount > 0)
                <form action="{{ route('student.notifications.markAllRead') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:border-slate-300 transition shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Mark All as Read
                    </button>
                </form>
            @endif

            @if ($totalCount > $unreadCount)
                <form action="{{ route('student.notifications.clearRead') }}" method="POST" onsubmit="return confirm('Clear all read notifications?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-semibold text-slate-500 hover:text-rose-600 hover:bg-rose-50/50 hover:border-rose-200 transition shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Clear Read
                    </button>
                </form>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-4 text-xs font-semibold text-emerald-800 flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3">
        <a href="{{ route('student.notifications.index', ['filter' => 'all']) }}"
           class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $filter === 'all' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <span>All Notifications</span>
            <span class="rounded-full px-1.5 py-0.5 text-[10px] {{ $filter === 'all' ? 'bg-indigo-200/70 text-indigo-800' : 'bg-slate-200 text-slate-700' }}">
                {{ $totalCount }}
            </span>
        </a>

        <a href="{{ route('student.notifications.index', ['filter' => 'unread']) }}"
           class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs font-bold transition {{ $filter === 'unread' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
            <span>Unread</span>
            @if ($unreadCount > 0)
                <span class="rounded-full px-1.5 py-0.5 text-[10px] bg-rose-500 text-white font-bold">
                    {{ $unreadCount }}
                </span>
            @else
                <span class="rounded-full px-1.5 py-0.5 text-[10px] bg-slate-200 text-slate-700">
                    0
                </span>
            @endif
        </a>
    </div>

    <!-- Notification List -->
    @if ($notifications->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center shadow-xs">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-slate-400 mb-4 ring-8 ring-slate-50">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900">
                {{ $filter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
            </h3>
            <p class="mt-1.5 max-w-sm text-xs leading-relaxed text-slate-500">
                {{ $filter === 'unread' ? 'You have read all your notifications. Check back later for updates.' : 'When you enroll in courses, complete lessons, or receive certificates, updates will appear here.' }}
            </p>
            <div class="mt-6">
                <a href="{{ route('student.courses') }}" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition">
                    Browse Courses &rarr;
                </a>
            </div>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($notifications as $notification)
                @php
                    $isUnread = $notification->unread();
                    $data = $notification->data;
                    $type = $data['type'] ?? 'general';
                    $title = $data['title'] ?? 'Notification';
                    $message = $data['message'] ?? '';
                    $actionUrl = $data['action_url'] ?? null;
                @endphp
                <div class="relative flex items-start justify-between gap-4 p-4 sm:p-5 rounded-2xl border transition {{ $isUnread ? 'bg-indigo-50/40 border-indigo-200/80 shadow-xs' : 'bg-white border-slate-200/80 hover:border-slate-300' }}">
                    <div class="flex items-start gap-3.5 flex-1 min-w-0">
                        <!-- Type Icon -->
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl shadow-2xs
                            @if($type === 'enrollment') bg-indigo-100 text-indigo-600
                            @elseif($type === 'payment') bg-emerald-100 text-emerald-600
                            @elseif($type === 'completion') bg-amber-100 text-amber-600
                            @elseif($type === 'certificate') bg-purple-100 text-purple-600
                            @elseif($type === 'announcement') bg-sky-100 text-sky-600
                            @else bg-slate-100 text-slate-600
                            @endif">
                            @if($type === 'enrollment')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            @elseif($type === 'payment')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            @elseif($type === 'completion')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                </svg>
                            @elseif($type === 'certificate')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                                </svg>
                            @elseif($type === 'announcement')
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                            @endif
                        </div>

                        <!-- Text Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-bold text-slate-900">
                                    {{ $title }}
                                </h3>
                                @if ($isUnread)
                                    <span class="inline-flex items-center rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-bold text-white shadow-2xs">
                                        New
                                    </span>
                                @endif
                                <span class="text-[11px] text-slate-400">
                                    &bull; {{ $notification->created_at->diffForHumans() }}
                                </span>
                            </div>

                            <p class="mt-1 text-xs text-slate-600 leading-relaxed">
                                {{ $message }}
                            </p>

                            @if ($actionUrl)
                                <div class="mt-2.5">
                                    @if ($isUnread)
                                        <form action="{{ route('student.notifications.read', $notification->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="target_url" value="{{ $actionUrl }}">
                                            <button type="submit" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-500 cursor-pointer">
                                                <span>View details</span>
                                                <span>&rarr;</span>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ $actionUrl }}" class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-500">
                                            <span>View details</span>
                                            <span>&rarr;</span>
                                        </a>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Right Quick Actions -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if ($isUnread)
                            <form action="{{ route('student.notifications.read', $notification->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" title="Mark as read" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded-lg hover:bg-white transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </form>
                        @endif

                        <form action="{{ route('student.notifications.destroy', $notification->id) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" title="Delete notification" class="p-1.5 text-slate-400 hover:text-rose-600 rounded-lg hover:bg-white transition cursor-pointer">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection