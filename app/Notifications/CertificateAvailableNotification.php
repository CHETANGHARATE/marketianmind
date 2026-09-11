<?php

namespace App\Notifications;

use App\Models\Certificate;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CertificateAvailableNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Certificate $certificate
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
            'type' => 'certificate',
            'title' => 'Certificate Ready to Download',
            'message' => 'Your verified completion certificate for "' . $this->certificate->course_title . '" is ready.',
            'action_url' => route('student.certificates.show', $this->certificate),
            'certificate_id' => $this->certificate->id,
            'certificate_number' => $this->certificate->certificate_number,
        ];
    }
}