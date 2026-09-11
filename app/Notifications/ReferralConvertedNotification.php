<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Referral;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReferralConvertedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Referral $referral,
        public Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $courseTitle = $this->order->course?->title ?? 'a course';
        $referredName = $this->referral->referred?->name ?? 'A referred friend';

        return [
            'type' => 'referral_converted',
            'title' => 'Referral Enrolled!',
            'message' => "{$referredName} enrolled in {$courseTitle} through your referral link.",
            'action_url' => route('student.referrals.index'),
            'referral_id' => $this->referral->id,
            'order_id' => $this->order->id,
        ];
    }
}