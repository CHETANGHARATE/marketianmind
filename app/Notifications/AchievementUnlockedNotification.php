<?php

namespace App\Notifications;

use App\Models\Achievement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AchievementUnlockedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Achievement $achievement
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
            'type' => 'achievement',
            'title' => 'Achievement Unlocked: ' . $this->achievement->name,
            'message' => 'Congratulations! You earned the "' . $this->achievement->name . '" badge. ' . $this->achievement->description,
            'action_url' => route('student.achievements.index'),
            'achievement_id' => $this->achievement->id,
            'points' => $this->achievement->points,
        ];
    }
}
