@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">Welcome to Marketian Mind, {{ $user->name }}! 🎉</h1>
    <p class="text">
        We're thrilled to have you join our community of founders, entrepreneurs, and small business owners dedicated to mastering practical, results-driven marketing.
    </p>

    <p class="text">
        Your account is fully activated. You can now access your student dashboard, track your progress, and enroll in practical courses designed to grow your business.
    </p>

    <div class="btn-box">
        <a href="{{ route('student.dashboard') }}" class="btn">
            Access Your Student Dashboard &rarr;
        </a>
    </div>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Account Email</span>
            <span class="info-value">{{ $user->email }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Account Type</span>
            <span class="info-value">Student Portal</span>
        </div>
        <div class="info-row">
            <span class="info-label">Catalog</span>
            <span class="info-value"><a href="{{ route('courses') }}" style="color: #4f46e5; text-decoration: none;">Browse Courses</a></span>
        </div>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        If you have any questions or need guidance on which course fits your current stage, simply visit our <a href="{{ route('contact') }}" style="color: #4f46e5;">help center</a>.
    </p>
@endsection