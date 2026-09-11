<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseEnrollmentNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course
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
            'type' => 'enrollment',
            'title' => 'Enrolled in ' . $this->course->title,
            'message' => 'You now have full access to "' . $this->course->title . '". Start learning today!',
            'action_url' => route('student.courses.show', $this->course),
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
        ];
    }
}