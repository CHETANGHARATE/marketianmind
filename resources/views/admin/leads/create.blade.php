@extends('layouts.admin')

@section('content')
<div class="max-w-4xl space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.leads.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition">
            &larr; Back to Leads Pipeline
        </a>
    </div>

    <div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-400 border border-amber-500/20">
                Manual Lead Entry
            </span>
            <span class="text-xs text-slate-400">Mini CRM</span>
        </div>
        <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl mt-1">
            Create New Lead
        </h1>
        <p class="text-xs text-slate-400 mt-1">
            Manually log a phone inquiry, walk-in client, event contact, or prospective business owner.
        </p>
    </div>

    @if ($errors->any())
        <div class="rounded-xl border border-rose-500/30 bg-rose-500/10 p-4">
            <div class="flex items-center gap-2 text-rose-400 font-bold text-xs">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                Please correct the errors below:
            </div>
            <ul class="mt-2 list-disc list-inside text-xs text-rose-300 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.leads.store') }}" class="space-y-6">
        @csrf

        <!-- Contact & Business Details Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3">
                1. Contact &amp; Prospect Information
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="name" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Full Name <span class="text-amber-400">*</span>
                    </label>
                    <input type="text"
                           name="name"
                           id="name"
                           required
                           value="{{ old('name') }}"
                           placeholder="e.g. Ramesh Patel"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Email Address <span class="text-amber-400">*</span>
                    </label>
                    <input type="email"
                           name="email"
                           id="email"
                           required
                           value="{{ old('email') }}"
                           placeholder="ramesh@example.com"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Phone Number
                    </label>
                    <input type="text"
                           name="phone"
                           id="phone"
                           value="{{ old('phone') }}"
                           placeholder="+91 98765 43210"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="company_name" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Company / Business Name
                    </label>
                    <input type="text"
                           name="company_name"
                           id="company_name"
                           value="{{ old('company_name') }}"
                           placeholder="e.g. Patel Retail Stores"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="job_title" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Job Title / Designation
                    </label>
                    <input type="text"
                           name="job_title"
                           id="job_title"
                           value="{{ old('job_title') }}"
                           placeholder="e.g. Founder &amp; CEO"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="city" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        City
                    </label>
                    <input type="text"
                           name="city"
                           id="city"
                           value="{{ old('city') }}"
                           placeholder="e.g. Mumbai"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="state" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        State / Province
                    </label>
                    <input type="text"
                           name="state"
                           id="state"
                           value="{{ old('state') }}"
                           placeholder="e.g. Maharashtra"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>

                <div>
                    <label for="country" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Country
                    </label>
                    <input type="text"
                           name="country"
                           id="country"
                           value="{{ old('country', 'India') }}"
                           placeholder="India"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                </div>
            </div>
        </div>

        <!-- Product Interest & Lead Attributes Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3">
                2. Pipeline Attributes &amp; Educational Interest
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Initial Pipeline Status <span class="text-amber-400">*</span>
                    </label>
                    <select name="status"
                            id="status"
                            required
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        @foreach(\App\Enums\LeadStatus::cases() as $st)
                            <option value="{{ $st->value }}" {{ old('status', 'new') === $st->value ? 'selected' : '' }}>
                                {{ $st->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="priority" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Priority Level <span class="text-amber-400">*</span>
                    </label>
                    <select name="priority"
                            id="priority"
                            required
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        @foreach(\App\Enums\LeadPriority::cases() as $pr)
                            <option value="{{ $pr->value }}" {{ old('priority', 'medium') === $pr->value ? 'selected' : '' }}>
                                {{ $pr->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="source" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Lead Source <span class="text-amber-400">*</span>
                    </label>
                    <input type="text"
                           name="source"
                           id="source"
                           required
                           list="source-suggestions"
                           value="{{ old('source', 'manual_admin') }}"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                    <datalist id="source-suggestions">
                        <option value="manual_admin">Manual Admin Entry</option>
                        <option value="phone_inquiry">Phone Inquiry</option>
                        <option value="walk_in">Walk-in / Office Visit</option>
                        <option value="direct_referral">Direct Referral</option>
                        <option value="exhibition">Exhibition / Event</option>
                        <option value="linkedin">LinkedIn Outreach</option>
                        <option value="webinar">Webinar Attendee</option>
                        <option value="website">Website Form</option>
                    </datalist>
                </div>

                <div>
                    <label for="assigned_to" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Assign Staff Member
                    </label>
                    <select name="assigned_to"
                            id="assigned_to"
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">-- Unassigned --</option>
                        @foreach($assignees as $staff)
                            <option value="{{ $staff->id }}" {{ old('assigned_to', auth()->id()) == $staff->id ? 'selected' : '' }}>
                                {{ $staff->name }} ({{ $staff->email }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="course_id" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Course Interest
                    </label>
                    <select name="course_id"
                            id="course_id"
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">-- None / General Inquiry --</option>
                        @foreach($courses as $course)
                            <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                {{ $course->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="bundle_id" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Bundle Interest
                    </label>
                    <select name="bundle_id"
                            id="bundle_id"
                            class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                        <option value="">-- None / Specific Bundle --</option>
                        @foreach($bundles as $bundle)
                            <option value="{{ $bundle->id }}" {{ old('bundle_id') == $bundle->id ? 'selected' : '' }}>
                                {{ $bundle->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <!-- Follow-up Scheduling & Initial Notes Card -->
        <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3">
                3. Follow-Up Schedule &amp; Initial Notes
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="next_follow_up_at" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Next Scheduled Follow-Up
                    </label>
                    <input type="datetime-local"
                           name="next_follow_up_at"
                           id="next_follow_up_at"
                           value="{{ old('next_follow_up_at') }}"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                    <p class="text-[11px] text-slate-500 mt-1">Leave empty if no immediate follow-up required.</p>
                </div>

                <div>
                    <label for="follow_up_status" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                        Follow-Up Action Type
                    </label>
                    <input type="text"
                           name="follow_up_status"
                           id="follow_up_status"
                           list="action-type-suggestions"
                           value="{{ old('follow_up_status', 'pending') }}"
                           placeholder="e.g. pending, call_back, send_syllabus"
                           class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                    <datalist id="action-type-suggestions">
                        <option value="pending">Pending</option>
                        <option value="call_scheduled">Phone Call Scheduled</option>
                        <option value="send_syllabus">Send Course Curriculum</option>
                        <option value="send_quote">Send Custom Pricing Quote</option>
                        <option value="demo_session">Arrange Demo Session</option>
                    </datalist>
                </div>
            </div>

            <div>
                <label for="initial_note" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Initial CRM Note / Inquiry Context
                </label>
                <textarea name="initial_note"
                          id="initial_note"
                          rows="4"
                          placeholder="Add key context from the conversation, business background, marketing goals, or requirements..."
                          class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">{{ old('initial_note') }}</textarea>
            </div>
        </div>

        <!-- Submit & Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.leads.index') }}"
               class="rounded-xl border border-slate-700 px-4 py-2.5 text-xs font-semibold text-slate-300 hover:text-white transition">
                Cancel
            </a>
            <button type="submit"
                    class="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-md">
                Create Lead Record
            </button>
        </div>
    </form>
</div>
@endsection
