<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseExpiringSoonMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Course $course,
        public int $daysRemaining,
        public ?CarbonInterface $expiresAt = null,
        public ?CourseAccessPeriod $accessPeriod = null
    ) {}

    public function envelope(): Envelope
    {
        $dayText = $this->daysRemaining === 1 ? '1 day' : "{$this->daysRemaining} days";

        return new Envelope(
            subject: "Reminder: Your access to {$this->course->title} expires in {$dayText} — Marketian Mind",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.course_expiring_soon',
        );
    }
}
