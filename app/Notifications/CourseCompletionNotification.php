<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseCompletionNotification extends Notification
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
            'type' => 'completion',
            'title' => 'Course Completed! 🎉',
            'message' => 'Congratulations! You have completed 100% of "' . $this->course->title . '".',
            'action_url' => route('student.my-learning'),
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
        ];
    }
}