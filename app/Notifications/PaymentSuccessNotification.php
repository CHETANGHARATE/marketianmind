<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Order $order
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
        $courseTitle = $this->order->course ? $this->order->course->title : 'Course';

        return [
            'type' => 'payment',
            'title' => 'Payment Confirmed',
            'message' => 'Your payment of ₹' . number_format($this->order->amount, 2) . ' for "' . $courseTitle . '" was successful.',
            'action_url' => route('student.orders.show', $this->order),
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number ?? (string) $this->order->id,
            'amount' => (float) $this->order->amount,
        ];
    }
}