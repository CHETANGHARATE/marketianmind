<?php

namespace App\Listeners;

use App\Events\EnrollmentExpired;
use App\Services\CourseRenewalNotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEnrollmentExpiredNotification
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected CourseRenewalNotificationService $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(EnrollmentExpired $event): void
    {
        try {
            $this->notificationService->sendAccessExpired($event->enrollment);
        } catch (Throwable $e) {
            Log::warning('Failed to handle EnrollmentExpired notification event', [
                'enrollment_id' => $event->enrollment->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
