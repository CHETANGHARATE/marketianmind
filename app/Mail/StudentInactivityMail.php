<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentInactivityMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public ?Lesson $nextLesson = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We've missed you! Continue learning {$this->course->title} — Marketian Mind",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student_inactivity',
        );
    }
}
