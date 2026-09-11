<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseCompletionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Course Completed: {$this->course->title} 🎉 — Marketian Mind",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course_completion',
        );
    }
}