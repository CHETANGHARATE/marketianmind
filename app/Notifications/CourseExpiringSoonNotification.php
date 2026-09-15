<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseAccessPeriod;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseExpiringSoonNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course,
        public int $daysRemaining,
        public ?CarbonInterface $expiresAt = null,
        public ?CourseAccessPeriod $accessPeriod = null
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $expiryDateFormatted = $this->expiresAt ? $this->expiresAt->format('M d, Y') : 'soon';
        $dayText = $this->daysRemaining === 1 ? '1 day' : "{$this->daysRemaining} days";

        return [
            'type' => 'course_expiring_soon',
            'title' => "Access to {$this->course->title} expires in {$dayText}",
            'message' => "Your access to \"{$this->course->title}\" will expire on {$expiryDateFormatted}. Renew early to maintain uninterrupted access.",
            'action_url' => route('student.courses.show', $this->course),
            'renewal_url' => route('student.courses.show', $this->course),
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'days_remaining' => $this->daysRemaining,
            'expires_at' => $this->expiresAt?->toISOString(),
            'access_period_id' => $this->accessPeriod?->id,
        ];
    }
}
