@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">Course Access Expired 🔒</h1>
    <p class="text">
        Hello {{ $user->name }}, your 1-year access period for <strong>{{ $course->title }}</strong> has ended.
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Course Title</span>
            <span class="info-value">{{ $course->title }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Access Status</span>
            <span class="info-value" style="color: #dc2626; font-weight: 700;">Expired (Paused)</span>
        </div>
        <div class="info-row">
            <span class="info-label">Course Progress</span>
            <span class="info-value" style="color: #16a34a; font-weight: 700;">100% Preserved</span>
        </div>
        <div class="info-row">
            <span class="info-label">Certificates Earned</span>
            <span class="info-value" style="color: #16a34a; font-weight: 700;">Permanently Valid</span>
        </div>
    </div>

    <p class="text">
        Don't worry — your completed lessons, quiz scores, notes, and any earned certificates remain permanently safe in your account. You can renew your access at any time to resume learning right where you left off.
    </p>

    <div class="btn-box">
        <a href="{{ route('student.courses.show', $course) }}" class="btn">
            Renew Access & Resume Learning &rarr;
        </a>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        You can review your certificates and learning portfolio anytime from <a href="{{ route('student.certificates.index') }}" style="color: #4f46e5;">Your Certificates</a>.
    </p>
@endsection
