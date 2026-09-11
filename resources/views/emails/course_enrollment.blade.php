@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">You're Enrolled! 🚀</h1>
    <p class="text">
        Hello {{ $user->name }}, you now have full access to <strong>{{ $course->title }}</strong>.
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Course Title</span>
            <span class="info-value">{{ $course->title }}</span>
        </div>
        @if($course->instructor_name)
            <div class="info-row">
                <span class="info-label">Instructor</span>
                <span class="info-value">{{ $course->instructor_name }}</span>
            </div>
        @endif
        @if($course->estimated_duration)
            <div class="info-row">
                <span class="info-label">Estimated Duration</span>
                <span class="info-value">{{ $course->estimated_duration }}</span>
            </div>
        @endif
        <div class="info-row">
            <span class="info-label">Access Type</span>
            <span class="info-value">Lifetime Self-Paced</span>
        </div>
    </div>

    <div class="btn-box">
        <a href="{{ route('student.courses.show', $course) }}" class="btn">
            Open Course Curriculum &rarr;
        </a>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        Track all your enrolled courses and ongoing modules in <a href="{{ route('student.my-learning') }}" style="color: #4f46e5;">My Learning</a>.
    </p>
@endsection