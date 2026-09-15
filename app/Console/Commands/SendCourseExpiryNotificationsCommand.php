<?php

namespace App\Console\Commands;

use App\Services\CourseRenewalNotificationService;
use Illuminate\Console\Command;

class SendCourseExpiryNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollments:send-expiry-notifications {--dry-run : Simulate notification checks without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan course enrollments and dispatch upcoming expiration reminders and notifications.';

    /**
     * Execute the console command.
     */
    public function handle(CourseRenewalNotificationService $service): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        $this->info($isDryRun ? 'Starting course expiry notification check (DRY-RUN)...' : 'Starting course expiry notification dispatch...');

        $stats = $service->processUpcomingExpirations($isDryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Eligible Enrollments Checked', $stats['checked']],
                ['30-Day Reminders Sent' . ($isDryRun ? ' (Simulated)' : ''), $stats['sent_30']],
                ['7-Day Reminders Sent' . ($isDryRun ? ' (Simulated)' : ''), $stats['sent_7']],
                ['1-Day Reminders Sent' . ($isDryRun ? ' (Simulated)' : ''), $stats['sent_1']],
                ['Skipped (Already Sent / Idempotent)', $stats['skipped_idempotent']],
                ['Skipped (Ineligible)', $stats['skipped_ineligible']],
            ]
        );

        $totalSent = $stats['sent_30'] + $stats['sent_7'] + $stats['sent_1'];
        $this->info("Completed. Total notifications " . ($isDryRun ? 'simulated: ' : 'dispatched: ') . $totalSent);

        return self::SUCCESS;
    }
}
