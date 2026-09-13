<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseProgressMilestoneNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Course $course,
        public string $milestone,
        public int $percentage
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
        $message = match ($this->milestone) {
            'milestone_25' => "Great start! You've completed 25% of \"{$this->course->title}\". Keep the momentum going!",
            'milestone_50' => "You're halfway there! You've reached 50% completion in \"{$this->course->title}\".",
            'milestone_75' => "You're almost there! 75% completed in \"{$this->course->title}\". Finish strong!",
            'near_completion' => "You're almost done with \"{$this->course->title}\"! Just a few lessons remaining to earn your certificate.",
            default => "Great progress! You've reached {$this->percentage}% of \"{$this->course->title}\".",
        };

        $title = match ($this->milestone) {
            'milestone_25' => '25% Course Milestone Reached! 🚀',
            'milestone_50' => 'Halfway There! 50% Milestone 🎯',
            'milestone_75' => '75% Completed! Almost There 🌟',
            'near_completion' => 'Nearly Done! Finish Strong 🏁',
            default => 'Course Progress Milestone',
        };

        return [
            'type' => 'progress_milestone',
            'milestone' => $this->milestone,
            'percentage' => $this->percentage,
            'title' => $title,
            'message' => $message,
            'action_url' => route('student.courses.show', $this->course),
            'course_id' => $this->course->id,
            'course_title' => $this->course->title,
        ];
    }
}
