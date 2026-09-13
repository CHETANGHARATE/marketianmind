<?php

namespace App\Console\Commands;

use App\Services\EngagementService;
use Illuminate\Console\Command;

class ProcessEngagementAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'engagement:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automated learning engagement and inactivity reminders.';

    /**
     * Execute the console command.
     */
    public function handle(EngagementService $engagementService): int
    {
        $this->info('Starting learning engagement automation processing...');

        $remindersCount = $engagementService->processInactivityReminders();

        $this->info("Completed. {$remindersCount} inactivity reminders sent.");

        return Command::SUCCESS;
    }
}
