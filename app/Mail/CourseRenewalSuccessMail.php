<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseRenewalSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public CourseAccessPeriod $accessPeriod,
        public ?Order $order = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Course Access Renewed: {$this->course->title} — Marketian Mind",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course_renewal_success',
        );
    }
}
