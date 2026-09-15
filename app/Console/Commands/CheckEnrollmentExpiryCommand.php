<?php

namespace App\Console\Commands;

use App\Enums\EnrollmentStatus;
use App\Events\EnrollmentExpired;
use App\Models\Enrollment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckEnrollmentExpiryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enrollments:check-expiry {--dry-run : Simulate expiry check without persisting changes to database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize past-expiry enrollments to expired status without modifying access dates or progress.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $now = now();
        $startTime = microtime(true);

        $this->info($isDryRun ? 'Starting enrollment expiry simulation (DRY-RUN)...' : 'Starting enrollment expiry synchronization...');

        $checkedCount = 0;
        $expiredCount = 0;

        try {
            // Query strictly eligible ACTIVE enrollments with complete access timestamps
            // Invariants:
            // 1. Excludes legacy lifetime enrollments (both dates NULL).
            // 2. Excludes partial-null dates.
            // 3. Excludes CANCELLED enrollments.
            // 4. Excludes COMPLETED enrollments (completion status & progress permanently preserved).
            // 5. Excludes already EXPIRED enrollments.
            $eligibleQuery = Enrollment::query()
                ->where('status', EnrollmentStatus::ACTIVE->value)
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now);

            $eligibleQuery->chunkById(200, function ($enrollments) use (&$checkedCount, &$expiredCount, $isDryRun) {
                foreach ($enrollments as $enrollment) {
                    $checkedCount++;

                    if ($isDryRun) {
                        $expiredCount++;
                        continue;
                    }

                    // Atomic conditional update guarantees concurrency safety and idempotency
                    $updated = Enrollment::query()
                        ->where('id', $enrollment->id)
                        ->where('status', EnrollmentStatus::ACTIVE->value)
                        ->where('expires_at', '<', now())
                        ->update(['status' => EnrollmentStatus::EXPIRED->value]);

                    if ($updated > 0) {
                        $expiredCount++;

                        // Dispatch lightweight event for future notification listeners (Phase 10.7)
                        $enrollment->status = EnrollmentStatus::EXPIRED;
                        event(new EnrollmentExpired($enrollment));
                    }
                }
            });

            // Inspect diagnostic metrics (completed expired access & partial-null dates)
            $completedExpiredCount = Enrollment::query()
                ->where('status', EnrollmentStatus::COMPLETED->value)
                ->whereNotNull('starts_at')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', $now)
                ->count();

            $partialNullCount = Enrollment::query()
                ->where(function ($q) {
                    $q->whereNull('starts_at')->whereNotNull('expires_at')
                      ->orWhereNotNull('starts_at')->whereNull('expires_at');
                })
                ->count();

            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            $this->info("Checked {$checkedCount} eligible active enrollments.");
            if ($isDryRun) {
                $this->warn("DRY-RUN: {$expiredCount} enrollments would be synchronized to EXPIRED.");
            } else {
                $this->info("Successfully synchronized {$expiredCount} enrollments to EXPIRED status.");
            }

            if ($completedExpiredCount > 0) {
                $this->line("Preserved {$completedExpiredCount} completed enrollments with expired access (course completion maintained).");
            }

            if ($partialNullCount > 0) {
                $this->line("Skipped {$partialNullCount} incomplete/partial-null date enrollments.");
            }

            $this->line("Execution completed in {$durationMs}ms.");

            Log::info('Enrollment expiry check completed', [
                'dry_run' => $isDryRun,
                'checked' => $checkedCount,
                'expired' => $expiredCount,
                'completed_expired_preserved' => $completedExpiredCount,
                'partial_null_skipped' => $partialNullCount,
                'duration_ms' => $durationMs,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to process enrollment expiry synchronization: ' . $e->getMessage());

            Log::error('Enrollment expiry check command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
