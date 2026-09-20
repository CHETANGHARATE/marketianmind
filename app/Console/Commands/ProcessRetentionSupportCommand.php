<?php

namespace App\Console\Commands;

use App\Services\RetentionService;
use Illuminate\Console\Command;

class ProcessRetentionSupportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retention:process-support {--dry-run : Simulate execution without dispatching notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automated learning support across active student retention cohorts (kick-start, re-engagement, and completion push).';

    /**
     * Execute the console command.
     */
    public function handle(RetentionService $retentionService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Running in DRY-RUN mode. No real notifications or emails will be dispatched.');
        } else {
            $this->info('Starting automated learning support processing...');
        }

        $stats = $retentionService->processAutomatedSupport($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Kick-start Reminders (3d+ not started)', $stats['kickstart_sent']],
                ['Re-engagement Reminders (14d+ inactive)', $stats['reengagement_sent']],
                ['Completion Push Reminders (>=80% done)', $stats['completion_push_sent']],
                ['Skipped (Cooldown Window)', $stats['skipped_cooldown']],
                ['Skipped (Unsubscribed)', $stats['skipped_unsubscribed']],
            ]
        );

        $this->info('Retention support processing completed successfully.');

        return Command::SUCCESS;
    }
}
