<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;

class DatabaseBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup {--type=all : Backup type: all, db, or files} {--prune : Automatically prune old backups}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform scheduled or manual database and file backups compatible with shared hosting';

    /**
     * Execute the console command.
     */
    public function handle(BackupService $backupService): int
    {
        $type = $this->option('type') ?: 'all';
        $shouldPrune = (bool) $this->option('prune');

        $this->info("Starting Marketian Mind backup (Type: {$type})...");

        $hasError = false;

        // 1. Database backup
        if (in_array($type, ['all', 'db'], true)) {
            $this->comment('Initiating database backup...');
            $dbResult = $backupService->backupDatabase();

            if ($dbResult['success']) {
                $this->info("✓ Database backup completed: {$dbResult['filename']} ({$dbResult['size_formatted']}) in {$dbResult['duration_ms']}ms");
            } else {
                $this->error("✗ Database backup failed: " . ($dbResult['error'] ?? 'Unknown error'));
                $hasError = true;
            }
        }

        // 2. User files backup
        if (in_array($type, ['all', 'files'], true)) {
            $this->comment('Initiating user uploads & files backup...');
            $filesResult = $backupService->backupFiles();

            if ($filesResult['success']) {
                $this->info("✓ Files backup completed: {$filesResult['filename']} ({$filesResult['size_formatted']}) in {$filesResult['duration_ms']}ms");
            } else {
                $this->error("✗ Files backup failed: " . ($filesResult['error'] ?? 'Unknown error'));
                $hasError = true;
            }
        }

        // 3. Optional pruning
        if ($shouldPrune) {
            $this->comment('Pruning old backups based on retention policy...');
            $pruneResult = $backupService->pruneOldBackups();
            $this->info("✓ Pruning finished: {$pruneResult['deleted_count']} old backup(s) removed, {$pruneResult['freed_formatted']} freed.");
        }

        $this->info('Backup process complete.');

        return $hasError ? Command::FAILURE : Command::SUCCESS;
    }
}
