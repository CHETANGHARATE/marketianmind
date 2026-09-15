@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">Your Course Access Expires Soon ⏳</h1>
    <p class="text">
        Hello {{ $user->name }}, your access period for <strong>{{ $course->title }}</strong> is approaching its expiration date.
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Course Title</span>
            <span class="info-value">{{ $course->title }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Time Remaining</span>
            <span class="info-value" style="color: #ea580c; font-weight: 700;">
                {{ $daysRemaining === 1 ? '1 day remaining' : $daysRemaining . ' days remaining' }}
            </span>
        </div>
        @if($expiresAt)
            <div class="info-row">
                <span class="info-label">Expiration Date</span>
                <span class="info-value">{{ $expiresAt->format('M d, Y') }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">Your Progress</span>
            <span class="info-value">Permanently Saved & Protected</span>
        </div>
    </div>

    <p class="text">
        Renew now to extend your access seamlessly without interruption. Early renewal will extend your access directly from your current expiration date, preserving your full remaining days!
    </p>

    <div class="btn-box">
        <a href="{{ route('student.courses.show', $course) }}" class="btn">
            Renew Course Access Now &rarr;
        </a>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        Need to review your current progress? You can always check your dashboard in <a href="{{ route('student.courses.show', $course) }}" style="color: #4f46e5;">My Learning</a>.
    </p>
@endsection
