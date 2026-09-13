<?php

namespace App\Http\Controllers;

use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Exception;

class HealthController extends Controller
{
    /**
     * Public lightweight health check endpoint for uptime monitors.
     * Never exposes credentials, database passwords, or stack traces.
     */
    public function publicCheck(): JsonResponse
    {
        $healthy = true;

        try {
            // Test DB connectivity with a fast ping
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $healthy = false;
            Log::channel('daily')->warning('Public health check probe database ping failed', [
                'error' => $e->getMessage(),
            ]);
        }

        $payload = [
            'status' => $healthy ? 'ok' : 'unhealthy',
            'app' => 'Marketian Mind',
            'timestamp' => now()->toIso8601String(),
        ];

        return response()
            ->json($payload, $healthy ? 200 : 503)
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    }

    /**
     * Authenticated Admin System Health & Diagnostic Dashboard.
     */
    public function adminDashboard(BackupService $backupService): View
    {
        // 1. Database Diagnostic
        $dbDiagnostic = [
            'status' => 'ok',
            'driver' => config('database.default'),
            'database' => '',
            'latency_ms' => null,
            'tables_count' => 0,
            'error' => null,
        ];

        try {
            $start = microtime(true);
            $pdo = DB::connection()->getPdo();
            $dbDiagnostic['latency_ms'] = round((microtime(true) - $start) * 1000, 2);
            $dbDiagnostic['database'] = DB::connection()->getDatabaseName();

            $driver = config("database.connections.{$dbDiagnostic['driver']}.driver");
            if ($driver === 'sqlite') {
                $tables = DB::select("SELECT count(*) as count FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
                $dbDiagnostic['tables_count'] = (int) ($tables[0]->count ?? 0);
            } else {
                $tables = DB::select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
                $dbDiagnostic['tables_count'] = count($tables);
            }
        } catch (Exception $e) {
            $dbDiagnostic['status'] = 'error';
            $dbDiagnostic['error'] = 'Database connection failed';
        }

        // 2. Storage & Disk Diagnostic
        $storagePath = storage_path('app');
        $storageDiagnostic = [
            'writable' => File::isWritable($storagePath),
            'storage_path' => $storagePath,
            'free_bytes' => @disk_free_space($storagePath) ?: null,
            'total_bytes' => @disk_total_space($storagePath) ?: null,
        ];
        $storageDiagnostic['free_formatted'] = $storageDiagnostic['free_bytes'] ? $backupService->formatBytes((int) $storageDiagnostic['free_bytes']) : 'N/A';
        $storageDiagnostic['total_formatted'] = $storageDiagnostic['total_bytes'] ? $backupService->formatBytes((int) $storageDiagnostic['total_bytes']) : 'N/A';
        $storageDiagnostic['used_percentage'] = ($storageDiagnostic['free_bytes'] && $storageDiagnostic['total_bytes'])
            ? round((1 - ($storageDiagnostic['free_bytes'] / $storageDiagnostic['total_bytes'])) * 100, 1)
            : null;

        // 3. Cache Diagnostic
        $cacheDiagnostic = [
            'store' => config('cache.default'),
            'operational' => false,
        ];
        try {
            $testKey = 'health_cache_probe_' . time();
            Cache::put($testKey, 'ok', 10);
            if (Cache::get($testKey) === 'ok') {
                $cacheDiagnostic['operational'] = true;
                Cache::forget($testKey);
            }
        } catch (Exception $e) {
            $cacheDiagnostic['operational'] = false;
        }

        // 4. Hostinger & PHP Environment Details
        $environment = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'https' => request()->isSecure(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Web Server',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time') . 's',
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
        ];

        // 5. Backups List
        $backups = $backupService->listBackups();
        $totalBackupBytes = array_sum(array_column($backups, 'size_bytes'));
        $totalBackupStorageFormatted = $backupService->formatBytes($totalBackupBytes);

        return view('admin.system.health', [
            'dbDiagnostic' => $dbDiagnostic,
            'storageDiagnostic' => $storageDiagnostic,
            'cacheDiagnostic' => $cacheDiagnostic,
            'environment' => $environment,
            'backups' => $backups,
            'totalBackupStorageFormatted' => $totalBackupStorageFormatted,
        ]);
    }

    /**
     * Trigger on-demand backup from admin panel.
     */
    public function triggerBackup(Request $request, BackupService $backupService): RedirectResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:db,files,all',
        ]);

        $type = $validated['type'];
        $results = [];

        if (in_array($type, ['all', 'db'], true)) {
            $results[] = $backupService->backupDatabase();
        }
        if (in_array($type, ['all', 'files'], true)) {
            $results[] = $backupService->backupFiles();
        }

        $allSuccess = !empty($results) && collect($results)->every(fn($r) => $r['success'] === true);

        if ($allSuccess) {
            return redirect()->route('admin.system.health')->with('success', 'Backup completed successfully.');
        }

        $errorMessage = collect($results)->firstWhere('success', false)['error'] ?? 'Backup process encountered an error.';
        return redirect()->route('admin.system.health')->with('error', 'Backup failed: ' . $errorMessage);
    }

    /**
     * Download an existing backup file securely.
     */
    public function downloadBackup(string $type, string $filename, BackupService $backupService): BinaryFileResponse
    {
        if (!in_array($type, ['db', 'files'], true)) {
            abort(404);
        }

        $path = $backupService->getBackupPath($type, $filename);
        if (!$path || !File::exists($path)) {
            abort(404, 'Backup file not found.');
        }

        return response()->download($path, basename($filename));
    }

    /**
     * Delete an existing backup file.
     */
    public function deleteBackup(string $type, string $filename, BackupService $backupService): RedirectResponse
    {
        if (!in_array($type, ['db', 'files'], true)) {
            abort(404);
        }

        $success = $backupService->deleteBackup($type, $filename);

        if ($success) {
            return redirect()->route('admin.system.health')->with('success', "Backup file '{$filename}' was deleted successfully.");
        }

        return redirect()->route('admin.system.health')->with('error', 'Unable to delete backup file.');
    }
}
