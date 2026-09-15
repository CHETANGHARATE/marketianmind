<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseAccessPeriod;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseAccessExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course,
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
        return [
            'type' => 'course_access_expired',
            'title' => "Access to {$this->course->title} has expired",
            'message' => "Your access period for \"{$this->course->title}\" has ended. All your progress and certificates are safely preserved. Renew anytime to resume learning.",
            'action_url' => route('student.courses.show', $this->course),
            'renewal_url' => route('student.courses.show', $this->course),
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'access_period_id' => $this->accessPeriod?->id,
        ];
    }
}
