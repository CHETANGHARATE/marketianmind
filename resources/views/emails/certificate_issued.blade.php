@extends('emails.layouts.transactional')

@section('content')
    <h1 class="headline">Your Certificate is Ready! 🏆</h1>
    <p class="text">
        Hello {{ $certificate->student_name }}, your verified completion certificate for <strong>{{ $certificate->course_title }}</strong> is available to view and download.
    </p>

    <div class="info-panel">
        <div class="info-row">
            <span class="info-label">Certificate ID</span>
            <span class="info-value" style="font-family: monospace;">{{ $certificate->certificate_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Course</span>
            <span class="info-value">{{ $certificate->course_title }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Issued Date</span>
            <span class="info-value">{{ $certificate->issued_at ? $certificate->issued_at->format('M d, Y') : date('M d, Y') }}</span>
        </div>
    </div>

    <div class="btn-box">
        <a href="{{ route('student.certificates.show', $certificate) }}" class="btn">
            View &amp; Download Certificate &rarr;
        </a>
    </div>

    <p class="text" style="margin-bottom: 0; font-size: 13px; color: #64748b;">
        You can also share this link with peers, team members, or clients as verified proof of course completion.
    </p>
@endsection