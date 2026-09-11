@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">Congratulations, {{ $user->name }}! 🎓</h1>
    <p class="text">
        You have successfully completed 100% of <strong>{{ $course->title }}</strong>!
    </p>

    <p class="text">
        Completing this curriculum demonstrates dedication to building real, repeatable marketing capabilities for your business. Take what you've learned, execute the playbooks, and scale your growth!
    </p>

    <div class="btn-box">
        <a href="{{ route('student.progress') }}" class="btn">
            View Learning Progress &rarr;
        </a>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        Your verified certificate of completion has also been generated and is ready to view.
    </p>
@endsection