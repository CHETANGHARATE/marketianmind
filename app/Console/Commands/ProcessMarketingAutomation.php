<?php

namespace App\Console\Commands;

use App\Services\MarketingAutomationService;
use Illuminate\Console\Command;

class ProcessMarketingAutomation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'automation:process {--batch=50 : The number of due executions to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due marketing automation email executions in batches.';

    /**
     * Execute the console command.
     */
    public function handle(MarketingAutomationService $service): int
    {
        $batchSize = (int) $this->option('batch');
        if ($batchSize <= 0) {
            $batchSize = 50;
        }

        $this->info("Processing due marketing automations (batch size: {$batchSize})...");

        $stats = $service->processDueExecutions($batchSize);

        $this->info("Processing completed:");
        $this->line("- Processed: {$stats['processed']}");
        $this->line("- Sent:      {$stats['sent']}");
        $this->line("- Skipped:   {$stats['skipped']}");
        $this->line("- Failed:    {$stats['failed']}");

        return Command::SUCCESS;
    }
}
