@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">Course Access Renewed! 🎉</h1>
    <p class="text">
        Hello {{ $user->name }}, thank you for renewing your access to <strong>{{ $course->title }}</strong>!
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Course Title</span>
            <span class="info-value">{{ $course->title }}</span>
        </div>
        @if($accessPeriod->starts_at)
            <div class="info-row">
                <span class="info-label">Valid From</span>
                <span class="info-value">{{ $accessPeriod->starts_at->format('M d, Y') }}</span>
            </div>
        @endif
        @if($accessPeriod->expires_at)
            <div class="info-row">
                <span class="info-label">Valid Through</span>
                <span class="info-value" style="color: #16a34a; font-weight: 700;">
                    {{ $accessPeriod->expires_at->format('M d, Y') }}
                </span>
            </div>
        @endif
        @if($order)
            <div class="info-row">
                <span class="info-label">Order Reference</span>
                <span class="info-value">{{ $order->order_number }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">Learning Progress</span>
            <span class="info-value">Fully Preserved & Unlocked</span>
        </div>
    </div>

    <p class="text">
        Your course access has been seamlessly extended. All course lessons, downloadable materials, quizzes, and community features are immediately ready for you.
    </p>

    <div class="btn-box">
        <a href="{{ route('student.courses.show', $course) }}" class="btn">
            Continue Learning &rarr;
        </a>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        View your complete purchase and renewal history anytime in <a href="{{ route('student.orders.index') }}" style="color: #4f46e5;">My Orders</a>.
    </p>
@endsection
