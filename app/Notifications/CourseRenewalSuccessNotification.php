<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\CourseAccessPeriod;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseRenewalSuccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course,
        public CourseAccessPeriod $accessPeriod,
        public ?Order $order = null
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
        $newExpiryFormatted = $this->accessPeriod->expires_at ? $this->accessPeriod->expires_at->format('M d, Y') : 'the end of your period';

        return [
            'type' => 'course_renewal_success',
            'title' => "Renewal Confirmed: {$this->course->title}",
            'message' => "Your access to \"{$this->course->title}\" has been successfully renewed through {$newExpiryFormatted}. Your progress and certificates remain seamlessly preserved.",
            'action_url' => route('student.courses.show', $this->course),
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'access_period_id' => $this->accessPeriod->id,
            'starts_at' => $this->accessPeriod->starts_at?->toISOString(),
            'expires_at' => $this->accessPeriod->expires_at?->toISOString(),
            'order_id' => $this->order?->id,
            'order_number' => $this->order?->order_number,
        ];
    }
}
