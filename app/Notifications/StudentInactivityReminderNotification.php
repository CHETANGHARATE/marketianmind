<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentInactivityReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course,
        public int $daysInactive = 7,
        public ?Lesson $nextLesson = null
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
        $actionUrl = $this->nextLesson
            ? route('student.courses.lessons.show', [$this->course, $this->nextLesson])
            : route('student.courses.show', $this->course);

        return [
            'type' => 'inactivity_reminder',
            'title' => "We've missed you! Continue your learning",
            'message' => "You've made great progress in \"{$this->course->title}\". Pick up right where you left off to keep building practical marketing skills.",
            'action_url' => $actionUrl,
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
        ];
    }
}
