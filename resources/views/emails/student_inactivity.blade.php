@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">We've Missed You! 👋</h1>
    <p class="text">
        Hello {{ $user->name }}, you've made commendable progress in <strong>{{ $course->title }}</strong>, and we'd love to help you cross the finish line.
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Active Course</span>
            <span class="info-value">{{ $course->title }}</span>
        </div>
        @if($nextLesson)
            <div class="info-row">
                <span class="info-label">Up Next</span>
                <span class="info-value">{{ $nextLesson->title }}</span>
            </div>
        @endif
        @if($course->estimated_duration)
            <div class="info-row">
                <span class="info-label">Estimated Pace</span>
                <span class="info-value">{{ $course->estimated_duration }}</span>
            </div>
        @endif
    </div>

    <div class="btn-box">
        @if($nextLesson)
            <a href="{{ route('student.courses.lessons.show', [$course, $nextLesson]) }}" class="btn">
                Continue Learning &rarr;
            </a>
        @else
            <a href="{{ route('student.courses.show', $course) }}" class="btn">
                Resume Course &rarr;
            </a>
        @endif
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        Consistent study habits build lasting marketing mastery. You can review your overall journey at any time in your <a href="{{ route('student.dashboard') }}" style="color: #4f46e5;">Student Dashboard</a>.
    </p>
@endsection
