@extends('layouts.student', ['title' => 'Refer Friends - Marketian Mind'])

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Refer Friends &amp; Colleagues</h1>
            <p class="text-sm text-slate-500 mt-1">
                Share practical online marketing with fellow business owners and founders.
            </p>
        </div>
    </div>

    <!-- Referral Link & Code Hero Card -->
    <div class="bg-gradient-to-br from-indigo-900 via-indigo-850 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-md relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-48 h-48 bg-indigo-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="max-w-2xl relative z-10">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 mb-4">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Your Personal Referral Hub
            </span>

            <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">
                Invite friends to Marketian Mind
            </h2>
            <p class="text-sm text-slate-300 mt-2 leading-relaxed">
                Send your unique referral link to peers. When they register and enroll in a course, their journey will be tracked right here.
            </p>

            <!-- Referral Link Box -->
            <div class="mt-6 flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <input
                        id="referral-url-input"
                        type="text"
                        readonly
                        value="{{ $referralUrl }}"
                        class="w-full bg-slate-950/60 border border-slate-700 rounded-xl px-4 py-3 text-sm text-indigo-200 font-mono focus:outline-none focus:border-indigo-400 select-all"
                    />
                </div>
                <button
                    type="button"
                    onclick="copyReferralLink()"
                    id="copy-btn"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-indigo-500 hover:bg-indigo-400 text-white font-semibold text-sm shadow-sm transition active:scale-95"
                >
                    <svg id="copy-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <span id="copy-text">Copy Link</span>
                </button>
            </div>

            <!-- Referral Code Snippet -->
            <div class="mt-4 flex items-center gap-2 text-xs text-slate-400">
                <span>Or share your unique code directly:</span>
                <span class="inline-block px-2.5 py-1 bg-white/10 text-white font-mono font-bold rounded-md border border-white/10">
                    {{ $referralCode }}
                </span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Friends Invited</span>
                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $totalReferrals }}</p>
            <p class="mt-1 text-xs text-slate-500">Accounts created via your link</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Enrolled (Converted)</span>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-emerald-600">{{ $convertedReferrals }}</p>
            <p class="mt-1 text-xs text-slate-500">Purchased and taking courses</p>
        </div>

        <div class="bg-white rounded-xl border border-slate-200/80 p-5 shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending Purchase</span>
                <span class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
            </div>
            <p class="mt-3 text-3xl font-extrabold text-amber-600">{{ $pendingReferrals }}</p>
            <p class="mt-1 text-xs text-slate-500">Registered free accounts</p>
        </div>
    </div>

    <!-- Referral Activity History -->
    <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Your Referral Activity</h3>
            <span class="text-xs text-slate-500">{{ $referrals->total() ?? 0 }} total invites</span>
        </div>

        @if($referrals->isEmpty())
            <div class="py-12 px-4 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                    </svg>
                </div>
                <h4 class="text-sm font-semibold text-slate-800">No referrals yet</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                    Copy your referral link above and share it on LinkedIn, WhatsApp, or email to invite your first friend.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-3.5">Friend</th>
                            <th class="px-6 py-3.5">Date Joined</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5">Enrolled Course</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-normal">
                        @foreach($referrals as $ref)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold text-xs">
                                            {{ substr($ref->referred?->name ?? 'F', 0, 1) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900">
                                                {{ $ref->referred ? Str::limit($ref->referred->name, 20) : 'Referred Student' }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $ref->created_at->format('M d, Y') }}
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
                                        <span class="font-medium text-slate-800">{{ $ref->order->course->title }}</span>
                                    @else
                                        <span class="text-slate-400">—</span>
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

<script>
function copyReferralLink() {
    const input = document.getElementById('referral-url-input');
    input.select();
    input.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(input.value).then(() => {
        const btnText = document.getElementById('copy-text');
        const orig = btnText.innerText;
        btnText.innerText = 'Copied!';
        setTimeout(() => {
            btnText.innerText = orig;
        }, 2000);
    });
}
</script>
@endsection