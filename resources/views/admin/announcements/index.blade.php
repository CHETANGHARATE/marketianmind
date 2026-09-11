@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">
                Announcements &amp; Notifications
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                Broadcast system notices, updates, or direct messages to registered students.
            </p>
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

    <!-- Metric Card -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Total Students</span>
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-2xl font-black text-slate-900">{{ number_format($studentsCount) }}</p>
            <p class="mt-1 text-xs text-slate-500">Eligible to receive platform announcements</p>
        </div>
    </div>

    <!-- Announcement Form -->
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-2xs">
        <h2 class="text-base font-bold text-slate-900 mb-1">Create Announcement</h2>
        <p class="text-xs text-slate-500 mb-6">Dispatched notifications will appear directly in the student's notification center.</p>

        <form action="{{ route('admin.announcements.store') }}" method="POST" class="space-y-5 max-w-2xl">
            @csrf

            <!-- Target Audience -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-2">
                    Target Audience <span class="text-rose-500">*</span>
                </label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-indigo-200 cursor-pointer transition">
                        <input type="radio" name="target" value="all" checked class="text-indigo-600 focus:ring-indigo-500" id="target-all">
                        <div>
                            <span class="block text-xs font-bold text-slate-900">All Registered Students</span>
                            <span class="block text-[11px] text-slate-500">{{ number_format($studentsCount) }} active students</span>
                        </div>
                    </label>
                    <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 hover:border-indigo-200 cursor-pointer transition">
                        <input type="radio" name="target" value="specific" class="text-indigo-600 focus:ring-indigo-500" id="target-specific">
                        <div>
                            <span class="block text-xs font-bold text-slate-900">Specific Student</span>
                            <span class="block text-[11px] text-slate-500">Target a single user</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Specific Student Select (Hidden by default unless selected) -->
            <div id="specific-student-field" class="hidden">
                <label for="user_id" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Select Student <span class="text-rose-500">*</span>
                </label>
                <select name="user_id" id="user_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">-- Choose a student --</option>
                    @foreach ($recentStudents as $student)
                        <option value="{{ $student->id }}">{{ $student->name }} ({{ $student->email }})</option>
                    @endforeach
                </select>
                @error('user_id')
                    <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Announcement Title <span class="text-rose-500">*</span>
                </label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="e.g., New Masterclass Live: Local SEO Mastery" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                @error('title')
                    <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Message -->
            <div>
                <label for="message" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Message Content <span class="text-rose-500">*</span>
                </label>
                <textarea name="message" id="message" rows="4" required placeholder="Write your announcement details..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 leading-relaxed">{{ old('message') }}</textarea>
                @error('message')
                    <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Action URL -->
            <div>
                <label for="action_url" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">
                    Action URL <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <input type="url" name="action_url" id="action_url" value="{{ old('action_url') }}" placeholder="https://example.com/courses/..." class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs text-slate-800 placeholder-slate-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                <p class="mt-1 text-[11px] text-slate-400">If specified, students can click directly to this URL from the notification.</p>
                @error('action_url')
                    <p class="mt-1 text-xs text-rose-500 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow-xs hover:bg-indigo-500 transition cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Send Announcement
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const targetAll = document.getElementById('target-all');
        const targetSpecific = document.getElementById('target-specific');
        const specificField = document.getElementById('specific-student-field');

        function toggleSpecificField() {
            if (targetSpecific.checked) {
                specificField.classList.remove('hidden');
            } else {
                specificField.classList.add('hidden');
            }
        }

        targetAll.addEventListener('change', toggleSpecificField);
        targetSpecific.addEventListener('change', toggleSpecificField);
        toggleSpecificField();
    });
</script>
@endsection