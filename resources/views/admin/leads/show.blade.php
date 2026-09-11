@extends('layouts.admin')

@section('content')
<div class="max-w-4xl space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.leads.index') }}" class="text-xs font-semibold text-slate-400 hover:text-white transition">
            &larr; Back to Leads &amp; Inquiries
        </a>
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold border {{ $lead->status->badgeClasses() }}">
            {{ $lead->status->label() }}
        </span>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                Inquiry from {{ $lead->name }}
            </h1>
            <p class="mt-1 text-xs text-slate-400">
                Received {{ $lead->created_at->format('M d, Y \a\t h:i A') }} ({{ $lead->created_at->diffForHumans() }})
            </p>
        </div>

        <form method="POST" action="{{ route('admin.leads.destroy', $lead) }}" onsubmit="return confirm('Are you sure you want to delete this inquiry?');">
            @csrf
            @method('DELETE')
            <button type="submit"
                    class="rounded-xl bg-rose-500/10 px-4 py-2 text-xs font-semibold text-rose-400 hover:bg-rose-500/20 border border-rose-500/20 transition">
                Delete Lead
            </button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Inquiry Content (2 cols) -->
        <div class="md:col-span-2 space-y-6">
            <!-- Details Card -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6 space-y-5">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3">
                    Inquiry Information
                </h2>

                <div>
                    <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Subject</span>
                    <p class="text-sm font-bold text-white mt-1">
                        {{ $lead->subject ?? 'General Course Inquiry' }}
                    </p>
                </div>

                <div>
                    <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Message / Details</span>
                    <div class="mt-1.5 rounded-lg border border-slate-800 bg-slate-950 p-4 text-xs sm:text-sm text-slate-200 leading-relaxed whitespace-pre-line">
                        {{ $lead->message ?: 'No additional message provided.' }}
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t border-slate-800/80">
                    <div>
                        <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Course Interested In</span>
                        <p class="text-xs font-bold text-indigo-400 mt-1">
                            @if($lead->course)
                                <a href="{{ route('admin.courses.edit', $lead->course) }}" class="hover:underline">
                                    {{ $lead->course->title }}
                                </a>
                            @else
                                <span class="text-slate-400 font-normal">None specified</span>
                            @endif
                        </p>
                    </div>

                    <div>
                        <span class="text-xs text-slate-500 uppercase tracking-wider font-semibold">Origin / Source</span>
                        <p class="text-xs font-semibold text-slate-300 mt-1 capitalize">
                            {{ str_replace('_', ' ', $lead->source) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Admin Notes & Status Form -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-6">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-3 mb-4">
                    Lead Management &amp; Internal Notes
                </h2>

                <form method="POST" action="{{ route('admin.leads.update', $lead) }}" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Status <span class="text-amber-400">*</span>
                        </label>
                        <select name="status"
                                id="status"
                                required
                                class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-sm text-white focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">
                            @foreach(\App\Enums\LeadStatus::cases() as $st)
                                <option value="{{ $st->value }}" {{ old('status', $lead->status->value) === $st->value ? 'selected' : '' }}>
                                    {{ $st->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="notes" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                            Internal Staff Notes <span class="text-slate-500 font-normal lowercase">(not visible to student)</span>
                        </label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="4"
                                  placeholder="e.g. Called on WhatsApp on Sept 11, interested in next cohort discount..."
                                  class="mt-1.5 w-full rounded-lg border border-slate-700 bg-slate-950 px-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:border-amber-500 focus:outline-hidden focus:ring-1 focus:ring-amber-500">{{ old('notes', $lead->notes) }}</textarea>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-5 py-2.5 text-xs font-bold text-slate-950 shadow-md hover:bg-amber-400 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Sidebar Info (1 col) -->
        <div class="space-y-6">
            <!-- Contact Card -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2">
                    Contact Details
                </h3>

                <div>
                    <span class="text-[11px] text-slate-500 font-semibold uppercase">Name</span>
                    <p class="text-sm font-bold text-white">{{ $lead->name }}</p>
                </div>

                <div>
                    <span class="text-[11px] text-slate-500 font-semibold uppercase">Email</span>
                    <p class="text-xs text-slate-300 break-all">
                        <a href="mailto:{{ $lead->email }}" class="text-amber-400 hover:underline">
                            {{ $lead->email }}
                        </a>
                    </p>
                </div>

                <div>
                    <span class="text-[11px] text-slate-500 font-semibold uppercase">Phone</span>
                    <p class="text-xs text-slate-300 font-mono">
                        {{ $lead->phone ?: 'Not provided' }}
                    </p>
                </div>

                <div>
                    <span class="text-[11px] text-slate-500 font-semibold uppercase">IP Address</span>
                    <p class="text-xs text-slate-400 font-mono">
                        {{ $lead->ip_address ?: 'Unknown' }}
                    </p>
                </div>
            </div>

            <!-- Student Account Status -->
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 border-b border-slate-800 pb-2 mb-3">
                    Student Account
                </h3>

                @if($matchedUser)
                    <div class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3">
                        <span class="text-xs font-bold text-emerald-400 block">Registered Student</span>
                        <p class="text-xs text-slate-300 mt-1">
                            User exists with ID #{{ $matchedUser->id }} ({{ $matchedUser->role->value }})
                        </p>
                        <a href="{{ route('admin.students.show', $matchedUser) }}" class="mt-2 inline-flex items-center text-xs font-semibold text-emerald-400 hover:underline">
                            View Student Profile &rarr;
                        </a>
                    </div>
                @else
                    <div class="rounded-lg border border-slate-800 bg-slate-950 p-3 text-center">
                        <span class="text-xs text-slate-400 block">Not Registered Yet</span>
                        <p class="text-[11px] text-slate-500 mt-1">
                            No student account registered with this email address.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection