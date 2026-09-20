@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('admin.leads.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition flex items-center gap-1">
                &larr; Back to Leads Pipeline
            </a>
            <div class="flex items-center gap-3 mt-2">
                <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Inquiry from {{ $lead->name }}
                </h1>
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-bold border {{ $lead->priority->badgeClasses() }}">
                    {{ $lead->priority->label() }}
                </span>
                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold border {{ $lead->status->badgeClasses() }}">
                    {{ $lead->status->label() }}
                </span>
                @if($lead->isConverted())
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/15 px-2.5 py-0.5 text-xs font-bold text-emerald-400 border border-emerald-500/30">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Converted Student
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Captured {{ $lead->created_at->format('M d, Y \a\t h:i A') }} ({{ $lead->created_at->diffForHumans() }})
                @if($lead->last_contacted_at)
                    &bull; Last contacted {{ $lead->last_contacted_at->diffForHumans() }}
                @endif
            </p>
        </div>

        <!-- Header Actions -->
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.leads.edit', $lead) }}"
               class="rounded-xl border border-slate-700 bg-slate-900 px-3.5 py-2 text-xs font-semibold text-slate-300 hover:bg-slate-800 hover:text-white transition">
                Edit Lead
            </a>

            <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Are you sure you want to delete this lead record?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="rounded-xl bg-rose-500/10 px-3.5 py-2 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Main 2-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content Area (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Lead Overview & Details Card -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3 flex items-center justify-between">
                    <span>Lead Overview &amp; Message</span>
                    <span class="text-slate-500 font-normal capitalize">Source: {{ str_replace('_', ' ', $lead->source) }}</span>
                </h2>

                <div>
                    <span class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Subject</span>
                    <p class="text-sm font-bold text-white mt-1">
                        {{ $lead->subject ?: 'General Inbound Inquiry' }}
                    </p>
                </div>

                <div>
                    <span class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Inquiry Message / Request Details</span>
                    <div class="mt-1.5 rounded-lg border border-slate-800 bg-slate-950 p-4 text-xs sm:text-sm text-slate-200 leading-relaxed whitespace-pre-line">
                        {{ $lead->message ?: 'No initial message text recorded.' }}
                    </div>
                </div>

                @php
                    $creationActivity = $lead->activities->firstWhere('activity_type', 'created');
                    $attribution = $creationActivity?->properties ?? [];
                    $hasAttribution = !empty($attribution['utm_source']) || !empty($attribution['utm_campaign']) || !empty($attribution['utm_medium']) || !empty($attribution['referrer']);
                @endphp

                @if($hasAttribution)
                    <div class="rounded-lg bg-slate-950/60 p-3 border border-slate-800/80 text-xs">
                        <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400 block mb-1.5">
                            Campaign &amp; Attribution Metadata
                        </span>
                        <div class="flex flex-wrap gap-2 text-[11px]">
                            @if(!empty($attribution['utm_campaign']))
                                <span class="rounded bg-slate-800 px-2 py-0.5 text-slate-300 font-mono">Campaign: {{ $attribution['utm_campaign'] }}</span>
                            @endif
                            @if(!empty($attribution['utm_source']))
                                <span class="rounded bg-slate-800 px-2 py-0.5 text-slate-300 font-mono">Source: {{ $attribution['utm_source'] }}</span>
                            @endif
                            @if(!empty($attribution['utm_medium']))
                                <span class="rounded bg-slate-800 px-2 py-0.5 text-slate-300 font-mono">Medium: {{ $attribution['utm_medium'] }}</span>
                            @endif
                            @if(!empty($attribution['utm_content']))
                                <span class="rounded bg-slate-800 px-2 py-0.5 text-slate-300 font-mono">Content: {{ $attribution['utm_content'] }}</span>
                            @endif
                            @if(!empty($attribution['referrer']))
                                <span class="rounded bg-slate-800 px-2 py-0.5 text-slate-300 font-mono truncate max-w-xs">Referrer: {{ $attribution['referrer'] }}</span>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Educational Interest & Business Meta -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-800/80">
                    <div>
                        <span class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Educational Interest</span>
                        <div class="mt-1">
                            @if($lead->course)
                                <a href="{{ route('admin.courses.edit', $lead->course) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-400 hover:underline">
                                    <span>Course: {{ $lead->course->title }}</span>
                                    <span class="text-slate-500">&rarr;</span>
                                </a>
                            @elseif($lead->bundle)
                                <a href="{{ route('admin.bundles.edit', $lead->bundle) }}" class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-400 hover:underline">
                                    <span>Bundle: {{ $lead->bundle->title }}</span>
                                    <span class="text-slate-500">&rarr;</span>
                                </a>
                            @else
                                <span class="text-xs text-slate-400">None specified (General Inquiry)</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Company / Organization</span>
                        <div class="mt-1 text-xs font-semibold text-white">
                            @if($lead->company_name)
                                {{ $lead->company_name }}
                                @if($lead->job_title)
                                    <span class="text-slate-400 font-normal">({{ $lead->job_title }})</span>
                                @endif
                            @else
                                <span class="text-slate-400 font-normal">Individual Entrepreneur / Not specified</span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <span class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Location</span>
                        <div class="mt-1 text-xs text-slate-300">
                            {{ collect([$lead->city, $lead->state, $lead->country])->filter()->join(', ') ?: 'Not specified' }}
                        </div>
                    </div>

                    <div>
                        <span class="text-[11px] text-slate-500 uppercase tracking-wider font-semibold">Assigned Staff</span>
                        <div class="mt-1 text-xs text-slate-300 font-semibold">
                            {{ $lead->assignedUser?->name ?: 'Unassigned' }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Follow-up Manager Card -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        Follow-Up Management
                    </h2>
                    @if($lead->next_follow_up_at)
                        @if($lead->isOverdue())
                            <span class="inline-flex items-center rounded-md bg-rose-500/10 px-2 py-0.5 text-xs font-bold text-rose-400 border border-rose-500/20">
                                Overdue Follow-Up
                            </span>
                        @elseif($lead->isDueToday())
                            <span class="inline-flex items-center rounded-md bg-amber-500/10 px-2 py-0.5 text-xs font-bold text-amber-400 border border-amber-500/20">
                                Due Today
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-bold text-emerald-400 border border-emerald-500/20">
                                Scheduled
                            </span>
                        @endif
                    @else
                        <span class="text-xs text-slate-400">No Follow-Up Scheduled</span>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-950/60 rounded-xl p-4 border border-slate-800/80">
                    <div>
                        <span class="text-[11px] text-slate-400 uppercase tracking-wider font-semibold">Scheduled Time</span>
                        <div class="text-sm font-bold text-white mt-0.5">
                            @if($lead->next_follow_up_at)
                                {{ $lead->next_follow_up_at->format('l, F j, Y \a\t h:i A') }}
                                <span class="text-xs text-slate-400 font-normal">({{ $lead->next_follow_up_at->diffForHumans() }})</span>
                            @else
                                None scheduled
                            @endif
                        </div>
                        @if($lead->follow_up_status)
                            <div class="text-xs text-slate-400 mt-1">
                                Action: <span class="text-amber-400 font-mono">{{ $lead->follow_up_status }}</span>
                            </div>
                        @endif
                    </div>

                    @if($lead->next_follow_up_at)
                        <form method="POST" action="{{ route('admin.leads.follow-up.complete', $lead) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-500/15 px-3.5 py-2 text-xs font-bold text-emerald-400 hover:bg-emerald-500/25 border border-emerald-500/30 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Mark Follow-Up Completed
                            </button>
                        </form>
                    @endif
                </div>

                <!-- Quick Reschedule / Set Follow-up Form -->
                <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="status" value="{{ $lead->status->value }}">
                    <input type="hidden" name="priority" value="{{ $lead->priority->value }}">
                    <input type="hidden" name="assigned_to" value="{{ $lead->assigned_to }}">

                    <div>
                        <label for="next_follow_up_at_input" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Reschedule Follow-Up
                        </label>
                        <input type="datetime-local"
                               name="next_follow_up_at"
                               id="next_follow_up_at_input"
                               value="{{ $lead->next_follow_up_at ? $lead->next_follow_up_at->format('Y-m-d\TH:i') : '' }}"
                               class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                    </div>

                    <div>
                        <label for="follow_up_status_input" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                            Action Type
                        </label>
                        <input type="text"
                               name="follow_up_status"
                               id="follow_up_status_input"
                               value="{{ $lead->follow_up_status ?? 'call_back' }}"
                               placeholder="e.g. call_back, send_details"
                               class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                    </div>

                    <div class="flex items-end">
                        <button type="submit"
                                class="w-full rounded-lg bg-slate-800 px-3.5 py-2 text-xs font-semibold text-white hover:bg-slate-700 border border-slate-700 transition">
                            Save Schedule
                        </button>
                    </div>
                </form>
            </div>

            <!-- Internal Staff Notes Feed -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3 flex items-center justify-between">
                    <span>Internal CRM Notes ({{ $lead->leadNotes->count() }})</span>
                    <span class="text-slate-400 font-normal lowercase text-[11px]">Private to admin staff</span>
                </h2>

                <!-- Add Note Form -->
                <form method="POST" action="{{ route('admin.leads.notes.store', $lead) }}" class="space-y-3">
                    @csrf
                    <div>
                        <textarea name="content"
                                  rows="3"
                                  required
                                  placeholder="Record call summary, client objection, pricing discussed, or next steps..."
                                  class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden"></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button type="submit"
                                class="rounded-lg bg-amber-500 px-4 py-2 text-xs font-bold text-slate-950 hover:bg-amber-400 transition shadow-xs">
                            Add Internal Note
                        </button>
                    </div>
                </form>

                <!-- Notes Timeline -->
                <div class="space-y-3 pt-2">
                    @forelse($lead->leadNotes as $note)
                        <div class="rounded-lg border border-slate-800/80 bg-slate-950/60 p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-white">{{ $note->author?->name ?: 'Admin Staff' }}</span>
                                    <span class="text-[10px] text-slate-400">&bull; {{ $note->created_at->format('M d, Y \a\t h:i A') }} ({{ $note->created_at->diffForHumans() }})</span>
                                </div>
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed whitespace-pre-line">
                                {{ $note->content }}
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic py-2">
                            No internal notes added yet. Use the form above to record staff notes.
                        </p>
                    @endforelse
                </div>
            </div>

            <!-- Activity History & Audit Log -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-4">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3">
                    Activity &amp; Audit Trail ({{ $lead->activities->count() }})
                </h2>

                <div class="space-y-3">
                    @forelse($lead->activities as $activity)
                        <div class="flex items-start gap-3 text-xs">
                            <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-800 text-amber-400 border border-slate-700 text-[10px] font-bold mt-0.5">
                                &bull;
                            </div>
                            <div class="flex-1">
                                <p class="text-slate-200">
                                    <span class="font-bold text-white">{{ $activity->user?->name ?: 'System' }}</span>:
                                    {{ $activity->description }}
                                </p>
                                <span class="text-[10px] text-slate-400">
                                    {{ $activity->created_at->format('M d, Y \a\t h:i A') }} ({{ $activity->created_at->diffForHumans() }})
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 italic">No activity entries recorded yet.</p>
                    @endforelse
                </div>
            </div>

        </div>

        <!-- Sidebar Column (1 Col) -->
        <div class="space-y-6">

            <!-- Student Conversion Card -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">
                    Student Conversion
                </h3>

                @if($lead->isConverted())
                    <div class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 p-4 space-y-2">
                        <div class="flex items-center gap-2 text-emerald-400 font-bold text-xs">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Converted into Student
                        </div>
                        <p class="text-xs text-slate-300">
                            Converted {{ $lead->converted_at ? $lead->converted_at->format('M d, Y \a\t h:i A') : 'Previously' }}
                        </p>
                        @if($lead->convertedUser)
                            <a href="{{ route('admin.students.show', $lead->convertedUser) }}"
                               class="inline-flex items-center gap-1 text-xs font-bold text-emerald-400 hover:underline mt-1">
                                View Student Profile ({{ $lead->convertedUser->name }}) &rarr;
                            </a>
                        @endif
                    </div>
                @else
                    @if($matchedUser)
                        <div class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 space-y-2">
                            <span class="text-xs font-bold text-amber-400 block">Existing Registered User Found</span>
                            <p class="text-[11px] text-slate-300">
                                A user with email <strong>{{ $matchedUser->email }}</strong> is already registered (#{{ $matchedUser->id }}).
                            </p>
                            <form method="POST" action="{{ route('admin.leads.convert', $lead) }}">
                                @csrf
                                <button type="submit"
                                        class="w-full rounded-lg bg-emerald-500 px-3 py-2 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition shadow-xs">
                                    Link &amp; Convert to Student
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="space-y-3">
                            <p class="text-xs text-slate-400">
                                Convert this lead into a registered student account. If not already registered, an account will be created.
                            </p>
                            <form method="POST" action="{{ route('admin.leads.convert', $lead) }}" class="space-y-3">
                                @csrf
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1">
                                        Optional Initial Password
                                    </label>
                                    <input type="password"
                                           name="password"
                                           placeholder="Leave empty for auto-generated password"
                                           class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden">
                                </div>
                                <button type="submit"
                                        class="w-full rounded-lg bg-emerald-500 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-emerald-400 transition shadow-xs">
                                    Convert to Student Account
                                </button>
                            </form>
                        </div>
                    @endif
                @endif

                @if(isset($associatedOrders) && $associatedOrders->isNotEmpty())
                    <div class="mt-4 pt-3 border-t border-slate-800">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
                            Associated Purchase Orders ({{ $associatedOrders->count() }})
                        </span>
                        <div class="space-y-2">
                            @foreach($associatedOrders as $ord)
                                <div class="rounded-lg bg-slate-950/80 p-2.5 border border-slate-800 text-xs flex items-center justify-between">
                                    <div class="min-w-0 pr-2">
                                        <a href="{{ route('admin.orders.show', $ord) }}" class="font-bold text-indigo-400 hover:underline truncate block">
                                            {{ $ord->order_number }}
                                        </a>
                                        <p class="text-[11px] text-slate-400 mt-0.5 truncate">
                                            {{ $ord->productTitle() }} &bull; {{ $ord->formattedAmount() }}
                                        </p>
                                    </div>
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold shrink-0 {{ $ord->status->badgeClasses() }}">
                                        {{ $ord->status->label() }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Quick Status & Assignment Form -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">
                    Pipeline Controls
                </h3>

                <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="quick_status" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Status
                        </label>
                        <select name="status"
                                id="quick_status"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                            @foreach(\App\Enums\LeadStatus::cases() as $st)
                                <option value="{{ $st->value }}" {{ $lead->status->value === $st->value ? 'selected' : '' }}>
                                    {{ $st->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="quick_priority" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Priority
                        </label>
                        <select name="priority"
                                id="quick_priority"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                            @foreach(\App\Enums\LeadPriority::cases() as $pr)
                                <option value="{{ $pr->value }}" {{ $lead->priority->value === $pr->value ? 'selected' : '' }}>
                                    {{ $pr->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="quick_assigned_to" class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">
                            Assignee
                        </label>
                        <select name="assigned_to"
                                id="quick_assigned_to"
                                class="mt-1 w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2 text-xs text-white focus:border-amber-500 focus:outline-hidden">
                            <option value="">-- Unassigned --</option>
                            @foreach($assignees as $staff)
                                <option value="{{ $staff->id }}" {{ $lead->assigned_to == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700 border border-slate-700 transition">
                        Update Status &amp; Assignee
                    </button>
                </form>
            </div>

            <!-- Contact Card -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">
                    Contact Details
                </h3>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Name</span>
                    <p class="text-sm font-bold text-white">{{ $lead->name }}</p>
                </div>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Email</span>
                    <p class="text-xs text-slate-300 break-all">
                        <a href="mailto:{{ $lead->email }}" class="text-amber-400 hover:underline">
                            {{ $lead->email }}
                        </a>
                    </p>
                </div>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Phone</span>
                    <p class="text-xs text-slate-300 font-mono">
                        @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}" class="hover:underline">{{ $lead->phone }}</a>
                            @if($lead->phone_normalized && $lead->phone_normalized !== $lead->phone)
                                <span class="block text-[10px] text-emerald-400 font-mono">Normalized: {{ $lead->phone_normalized }}</span>
                            @endif
                        @else
                            <span class="text-slate-400 italic">Not provided</span>
                        @endif
                    </p>
                </div>

                <!-- WhatsApp Status & Actions -->
                <div class="pt-3 border-t border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] text-slate-400 font-semibold uppercase">WhatsApp Status</span>
                        @if($lead->whatsapp_opt_in)
                            <span class="inline-flex items-center gap-1 rounded-md bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-400 border border-emerald-500/20">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                                Opted In
                            </span>
                        @elseif($lead->whatsapp_opted_out_at)
                            <span class="inline-flex items-center gap-1 rounded-md bg-rose-500/10 px-2 py-0.5 text-[10px] font-bold text-rose-400 border border-rose-500/20">
                                Opted Out
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-md bg-slate-700/20 px-2 py-0.5 text-[10px] font-bold text-slate-400 border border-slate-700/40">
                                Not Opted In
                            </span>
                        @endif
                    </div>

                    @php
                        $lastWhatsApp = $lead->whatsappMessages()->latest()->first();
                        $activeTemplates = \App\Models\WhatsAppTemplate::active()->get();
                    @endphp

                    @if($lastWhatsApp)
                        <div class="rounded-lg bg-slate-950 p-2.5 border border-slate-800 text-[11px] space-y-1">
                            <div class="flex items-center justify-between text-slate-400">
                                <span>Last Message</span>
                                <span class="font-medium text-emerald-400 uppercase text-[10px]">{{ $lastWhatsApp->status->value ?? $lastWhatsApp->status }}</span>
                            </div>
                            <p class="text-slate-300 font-mono text-[10px] truncate">{{ $lastWhatsApp->template?->name ?: 'Direct' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $lastWhatsApp->created_at->diffForHumans() }}</p>
                        </div>
                    @endif

                    @if($lead->phone && $activeTemplates->isNotEmpty())
                        <div class="pt-2">
                            <button type="button"
                                    onclick="document.getElementById('whatsapp-send-form').classList.toggle('hidden')"
                                    class="w-full flex items-center justify-center gap-2 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/30 border border-emerald-500/30 px-3 py-2 text-xs font-bold text-emerald-300 transition">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                Send WhatsApp Message
                            </button>

                            <form id="whatsapp-send-form" method="POST" action="{{ route('admin.whatsapp.manual-send') }}" class="hidden mt-3 space-y-3 p-3 bg-slate-950 rounded-lg border border-slate-800">
                                @csrf
                                <input type="hidden" name="recipient_type" value="lead">
                                <input type="hidden" name="recipient_id" value="{{ $lead->id }}">
                                @if($lead->course_id)
                                    <input type="hidden" name="course_id" value="{{ $lead->course_id }}">
                                @endif
                                @if($lead->bundle_id)
                                    <input type="hidden" name="bundle_id" value="{{ $lead->bundle_id }}">
                                @endif

                                <div>
                                    <label class="block text-[10px] font-semibold text-slate-400 uppercase mb-1">Select Template</label>
                                    <select name="template_id" required class="w-full rounded-lg border border-slate-700 bg-slate-900 px-2.5 py-1.5 text-xs text-white focus:border-emerald-500 focus:outline-none">
                                        @foreach($activeTemplates as $tmpl)
                                            <option value="{{ $tmpl->id }}">
                                                {{ $tmpl->name }} ({{ ucfirst($tmpl->category->value) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <button type="submit" class="w-full rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs py-1.5 transition">
                                    Dispatch Message
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">Location</span>
                    <p class="text-xs text-slate-300">
                        {{ collect([$lead->city, $lead->state, $lead->country])->filter()->join(', ') ?: 'Not provided' }}
                    </p>
                </div>

                <div>
                    <span class="text-[10px] text-slate-400 font-semibold uppercase">IP Address</span>
                    <p class="text-xs text-slate-400 font-mono">
                        {{ $lead->ip_address ?: 'Unknown' }}
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
