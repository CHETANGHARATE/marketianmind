<?php

namespace App\Notifications;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentLearningSupportNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course,
        public string $supportType,
        public ?Lesson $nextLesson = null,
        public array $customData = []
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
        $actionUrl = match ($this->supportType) {
            'renewal_reminder' => route('courses.show', $this->course),
            default => $this->nextLesson
                ? route('student.courses.lessons.show', [$this->course, $this->nextLesson])
                : route('student.courses.show', $this->course),
        };

        $pct = $this->customData['percentage'] ?? 0;

        [$title, $message] = match ($this->supportType) {
            'kickstart' => [
                "Welcome! Start your first lesson in {$this->course->title}",
                "You're enrolled in practical marketing education. Take 10 minutes today to start your first lesson!",
            ],
            'reengagement' => [
                "Keep up your momentum in {$this->course->title}",
                "It's been a little while since your last lesson. Pick up right where you left off and advance your skills.",
            ],
            'completion_push' => [
                "You're almost there! Finish {$this->course->title} to earn your certificate",
                "You've completed {$pct}% of the curriculum! Finish your remaining lessons to receive your accredited certificate.",
            ],
            'renewal_reminder' => [
                "Extend your access to {$this->course->title}",
                "Your 365-day access validity can be extended with manual renewal. Your learning progress and certificate records remain permanently preserved.",
            ],
            default => [
                "Update regarding {$this->course->title}",
                "Continue your practical learning journey in {$this->course->title}.",
            ],
        };

        return [
            'type' => 'learning_support_' . $this->supportType,
            'support_type' => $this->supportType,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
            'metadata' => $this->customData,
        ];
    }
}
