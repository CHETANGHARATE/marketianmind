@props([
    'course',
    'enrollment' => null,
])

@php
    if (! $enrollment) {
        return;
    }

    $state = $enrollment->getAccessState();
    $daysRemaining = $enrollment->getRemainingDays();
    $formattedExpiry = $enrollment->getFormattedExpiryDate('d M Y');
    $canRenew = $enrollment->canRenew();
    $renewalLabel = $enrollment->getRenewalCtaLabel();
@endphp

@if($state === 'expiring')
    <div class="rounded-2xl border border-amber-300 bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-xs">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-sm sm:text-base font-black text-amber-950">
                            Course Access Expiring Soon
                        </h3>
                        <span class="inline-flex items-center rounded-full bg-amber-200/80 px-2.5 py-0.5 text-[10px] font-bold text-amber-900 border border-amber-300">
                            {{ $enrollment->getRemainingDaysText() }}
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-amber-800 leading-relaxed">
                        Your access period for <strong>{{ $course->title }}</strong> expires on <strong>{{ $formattedExpiry }}</strong>. Renew early to extend your validity seamlessly without losing any remaining days.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-center">
                <form action="{{ route('student.courses.purchase', $course) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-amber-600 hover:bg-amber-500 text-white px-5 py-2.5 text-xs font-bold shadow-xs transition cursor-pointer">
                        {{ $renewalLabel }} &rarr;
                    </button>
                </form>
            </div>
        </div>
    </div>
@elseif($state === 'expired')
    <div class="rounded-2xl border border-rose-300 bg-gradient-to-r from-rose-50 via-red-50 to-rose-50 p-5 sm:p-6 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3.5">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-rose-600 text-white shadow-xs">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 class="text-sm sm:text-base font-black text-rose-950">
                            Course Access Expired
                        </h3>
                        <span class="inline-flex items-center rounded-full bg-rose-200/80 px-2.5 py-0.5 text-[10px] font-bold text-rose-900 border border-rose-300">
                            Access Paused
                        </span>
                    </div>
                    <p class="mt-1 text-xs text-rose-800 leading-relaxed">
                        Your access period ended{{ $formattedExpiry ? ' on ' . $formattedExpiry : '' }}. Your completed lessons, quiz scores, and earned certificates are <strong>permanently preserved</strong>. Renew access to resume learning.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5 shrink-0 self-end sm:self-center">
                <form action="{{ route('student.courses.purchase', $course) }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-rose-600 hover:bg-rose-500 text-white px-5 py-2.5 text-xs font-bold shadow-xs transition cursor-pointer">
                        {{ $renewalLabel }} &rarr;
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
