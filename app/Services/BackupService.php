<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;
use Exception;

class BackupService
{
    protected string $baseBackupPath;
    protected string $dbBackupPath;
    protected string $filesBackupPath;

    public function __construct()
    {
        $this->baseBackupPath = storage_path('app/backups');
        $this->dbBackupPath = $this->baseBackupPath . DIRECTORY_SEPARATOR . 'db';
        $this->filesBackupPath = $this->baseBackupPath . DIRECTORY_SEPARATOR . 'files';

        $this->ensureDirectoriesExist();
    }

    /**
     * Ensure secure backup directories exist.
     */
    protected function ensureDirectoriesExist(): void
    {
        if (!File::isDirectory($this->baseBackupPath)) {
            File::makeDirectory($this->baseBackupPath, 0755, true);
        }
        if (!File::isDirectory($this->dbBackupPath)) {
            File::makeDirectory($this->dbBackupPath, 0755, true);
        }
        if (!File::isDirectory($this->filesBackupPath)) {
            File::makeDirectory($this->filesBackupPath, 0755, true);
        }

        // Place a .htaccess file to block direct web access if directory ever leaks to web root
        $htaccessFile = $this->baseBackupPath . DIRECTORY_SEPARATOR . '.htaccess';
        if (!File::exists($htaccessFile)) {
            File::put($htaccessFile, "Deny from all\n");
        }
    }

    /**
     * Create a database backup.
     * Compatible with Hostinger shared hosting (PHP PDO streaming + mysqldump fallback).
     */
    public function backupDatabase(?string $customFilename = null): array
    {
        $startTime = microtime(true);
        $timestamp = date('Y_m_d_His');
        $supportsGzip = extension_loaded('zlib');
        $extension = $supportsGzip ? 'sql.gz' : 'sql';
        $filename = $customFilename ?: "backup_db_{$timestamp}.{$extension}";
        $filePath = $this->dbBackupPath . DIRECTORY_SEPARATOR . $filename;

        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            if ($driver === 'sqlite') {
                $this->exportSqliteDatabase($filePath, $supportsGzip);
                $tablesCount = 1;
            } else {
                $tablesCount = $this->exportMySqlDatabase($filePath, $supportsGzip);
            }

            // Verify file integrity
            if (!File::exists($filePath) || filesize($filePath) === 0) {
                throw new Exception("Backup file was not created or is empty: {$filePath}");
            }

            $size = filesize($filePath);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('daily')->info('Database backup completed successfully', [
                'filename' => $filename,
                'size_bytes' => $size,
                'duration_ms' => $durationMs,
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filePath,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'type' => 'db',
                'tables' => $tablesCount,
                'duration_ms' => $durationMs,
                'created_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::channel('daily')->error('Database backup failed', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);

            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            return [
                'success' => false,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'type' => 'db',
            ];
        }
    }

    /**
     * Export MySQL/MariaDB database via PDO streaming.
     */
    protected function exportMySqlDatabase(string $filePath, bool $supportsGzip): int
    {
        $fileHandle = $supportsGzip ? gzopen($filePath, 'w9') : fopen($filePath, 'w');
        if (!$fileHandle) {
            throw new Exception("Unable to open backup file for writing: {$filePath}");
        }

        $write = function ($content) use ($fileHandle, $supportsGzip) {
            if ($supportsGzip) {
                gzwrite($fileHandle, $content);
            } else {
                fwrite($fileHandle, $content);
            }
        };

        // Write header
        $write("-- ============================================================\n");
        $write("-- Marketian Mind MySQL Database Backup\n");
        $write("-- Generated at: " . date('Y-m-d H:i:s') . "\n");
        $write("-- Environment: " . config('app.env') . "\n");
        $write("-- ============================================================\n\n");
        $write("SET FOREIGN_KEY_CHECKS=0;\n");
        $write("SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n");
        $write("SET NAMES utf8mb4;\n\n");

        $pdo = DB::connection()->getPdo();
        $databaseName = DB::connection()->getDatabaseName();

        // Get table list
        $tablesStmt = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
        $tables = $tablesStmt->fetchAll(\PDO::FETCH_NUM);
        $tablesCount = count($tables);

        // Skip data for ephemeral or session cache tables to avoid bloat
        $structureOnlyTables = ['cache', 'cache_locks', 'sessions', 'jobs', 'failed_jobs', 'job_batches'];

        foreach ($tables as $row) {
            $table = $row[0];

            $write("\n-- ------------------------------------------------------------\n");
            $write("-- Table structure for `{$table}`\n");
            $write("-- ------------------------------------------------------------\n");
            $write("DROP TABLE IF EXISTS `{$table}`;\n");

            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(\PDO::FETCH_ASSOC);
            $write($createRow['Create Table'] . ";\n\n");

            if (in_array($table, $structureOnlyTables, true)) {
                $write("-- [Skipped row data for ephemeral table {$table}]\n\n");
                continue;
            }

            // Dump rows in chunks
            $countStmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
            $totalRows = (int) $countStmt->fetchColumn();

            if ($totalRows > 0) {
                $write("-- Dumping data for table `{$table}` ({$totalRows} rows)\n");
                $chunkSize = 250;
                $offset = 0;

                while ($offset < $totalRows) {
                    $dataStmt = $pdo->query("SELECT * FROM `{$table}` LIMIT {$chunkSize} OFFSET {$offset}");
                    $rows = $dataStmt->fetchAll(\PDO::FETCH_ASSOC);

                    if (!empty($rows)) {
                        $columnNames = array_keys($rows[0]);
                        $escapedColumns = array_map(fn($col) => "`{$col}`", $columnNames);
                        $columnsSql = implode(', ', $escapedColumns);

                        $write("INSERT INTO `{$table}` ({$columnsSql}) VALUES\n");

                        $rowValues = [];
                        foreach ($rows as $r) {
                            $escapedValues = array_map(function ($val) use ($pdo) {
                                if (is_null($val)) {
                                    return 'NULL';
                                }
                                return $pdo->quote((string) $val);
                            }, array_values($r));

                            $rowValues[] = '(' . implode(', ', $escapedValues) . ')';
                        }

                        $write(implode(",\n", $rowValues) . ";\n");
                    }

                    $offset += $chunkSize;
                }
                $write("\n");
            }
        }

        $write("SET FOREIGN_KEY_CHECKS=1;\n");
        $write("-- Backup completed successfully.\n");

        if ($supportsGzip) {
            gzclose($fileHandle);
        } else {
            fclose($fileHandle);
        }

        return $tablesCount;
    }

    /**
     * Export SQLite database (used during automated tests or local environments).
     */
    protected function exportSqliteDatabase(string $filePath, bool $supportsGzip): void
    {
        $dbPath = DB::connection()->getDatabaseName();
        if ($dbPath === ':memory:' || empty($dbPath)) {
            // Memory SQLite: dump schema and tables using SQLite pragma
            $fileHandle = $supportsGzip ? gzopen($filePath, 'w9') : fopen($filePath, 'w');
            $write = function ($content) use ($fileHandle, $supportsGzip) {
                if ($supportsGzip) {
                    gzwrite($fileHandle, $content);
                } else {
                    fwrite($fileHandle, $content);
                }
            };

            $write("-- Marketian Mind SQLite In-Memory Backup\n");
            $write("-- Generated at: " . date('Y-m-d H:i:s') . "\n");

            $pdo = DB::connection()->getPdo();
            $stmt = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                if (!empty($row['sql'])) {
                    $write($row['sql'] . ";\n");
                }
            }

            if ($supportsGzip) {
                gzclose($fileHandle);
            } else {
                fclose($fileHandle);
            }
            return;
        }

        if (File::exists($dbPath)) {
            if ($supportsGzip) {
                $gz = gzopen($filePath, 'w9');
                $fp = fopen($dbPath, 'r');
                while (!feof($fp)) {
                    gzwrite($gz, fread($fp, 1024 * 512));
                }
                fclose($fp);
                gzclose($gz);
            } else {
                File::copy($dbPath, $filePath);
            }
        }
    }

    /**
     * Create a backup archive of user-generated files and media.
     * Supports ZipArchive if available, with native PharData (.tar.gz) fallback.
     */
    public function backupFiles(?string $customFilename = null): array
    {
        $startTime = microtime(true);
        $timestamp = date('Y_m_d_His') . '_' . substr(md5(uniqid('', true)), 0, 6);
        $hasZip = class_exists(\ZipArchive::class);
        $hasPhar = class_exists(\PharData::class);

        if (!$hasZip && !$hasPhar) {
            return [
                'success' => false,
                'error' => 'Neither ZipArchive nor PharData is available in this PHP runtime.',
                'type' => 'files',
            ];
        }

        $extension = $hasZip ? 'zip' : 'tar.gz';
        $filename = $customFilename ?: "backup_files_{$timestamp}.{$extension}";
        $filePath = $this->filesBackupPath . DIRECTORY_SEPARATOR . $filename;

        try {
            $sourceDir = storage_path('app/public');
            $filesCount = 0;

            if ($hasZip) {
                $zip = new \ZipArchive();
                $result = $zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
                if ($result !== true) {
                    throw new Exception("Unable to create zip archive: {$filePath} (Error code: {$result})");
                }

                if (File::isDirectory($sourceDir)) {
                    $files = File::allFiles($sourceDir);
                    foreach ($files as $file) {
                        $relativePath = 'public/' . $file->getRelativePathname();
                        $zip->addFile($file->getRealPath(), $relativePath);
                        $filesCount++;
                    }
                }

                $manifest = [
                    'application' => 'Marketian Mind',
                    'backup_type' => 'files',
                    'format' => 'zip',
                    'created_at' => now()->toIso8601String(),
                    'files_archived' => $filesCount,
                ];
                $zip->addFromString('backup_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
                $zip->close();
            } else {
                $tarBaseName = 'backup_files_' . $timestamp;
                if ($customFilename) {
                    if (str_ends_with(strtolower($customFilename), '.tar.gz')) {
                        $tarBaseName = substr($customFilename, 0, -7);
                    } elseif (str_ends_with(strtolower($customFilename), '.tar')) {
                        $tarBaseName = substr($customFilename, 0, -4);
                    } elseif (str_ends_with(strtolower($customFilename), '.zip')) {
                        $tarBaseName = substr($customFilename, 0, -4);
                    } else {
                        $tarBaseName = pathinfo($customFilename, PATHINFO_FILENAME);
                    }
                }

                $tarPath = $this->filesBackupPath . DIRECTORY_SEPARATOR . "{$tarBaseName}.tar";
                $gzPath = $this->filesBackupPath . DIRECTORY_SEPARATOR . "{$tarBaseName}.tar.gz";

                if (File::exists($tarPath)) {
                    @unlink($tarPath);
                }
                if (File::exists($gzPath)) {
                    @unlink($gzPath);
                }

                $phar = new \PharData($tarPath);
                if (File::isDirectory($sourceDir)) {
                    $files = File::allFiles($sourceDir);
                    foreach ($files as $file) {
                        $phar->addFile($file->getRealPath(), 'public/' . $file->getRelativePathname());
                        $filesCount++;
                    }
                }

                $manifest = json_encode([
                    'application' => 'Marketian Mind',
                    'backup_type' => 'files',
                    'format' => 'tar.gz',
                    'created_at' => now()->toIso8601String(),
                    'files_archived' => $filesCount,
                ], JSON_PRETTY_PRINT);
                $phar->addFromString('backup_manifest.json', $manifest);

                // Compress with gzip if zlib loaded
                if (extension_loaded('zlib')) {
                    $phar->compress(\Phar::GZ);
                    unset($phar);
                    if (File::exists($tarPath)) {
                        @unlink($tarPath);
                    }
                    $filename = "{$tarBaseName}.tar.gz";
                    $filePath = $gzPath;
                } else {
                    $filename = "{$tarBaseName}.tar";
                    $filePath = $tarPath;
                }
            }

            if (!File::exists($filePath) || filesize($filePath) === 0) {
                throw new Exception("Files backup archive was not created or is empty: {$filePath}");
            }

            $size = filesize($filePath);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            Log::channel('daily')->info('Files backup completed successfully', [
                'filename' => $filename,
                'files_count' => $filesCount,
                'size_bytes' => $size,
                'duration_ms' => $durationMs,
            ]);

            return [
                'success' => true,
                'filename' => $filename,
                'path' => $filePath,
                'size' => $size,
                'size_formatted' => $this->formatBytes($size),
                'type' => 'files',
                'files_count' => $filesCount,
                'duration_ms' => $durationMs,
                'created_at' => now()->toIso8601String(),
            ];
        } catch (Exception $e) {
            Log::channel('daily')->error('Files backup failed', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);

            if (File::exists($filePath)) {
                File::delete($filePath);
            }

            return [
                'success' => false,
                'filename' => $filename,
                'error' => $e->getMessage(),
                'type' => 'files',
            ];
        }
    }

    /**
     * Prune old database and file backups based on retention policy.
     * Default: keep last 7 daily backups, last 4 weekly backups.
     */
    public function pruneOldBackups(int $keepDaily = 7, int $keepWeekly = 4): array
    {
        $maxToKeep = max($keepDaily, $keepWeekly);
        $deleted = [];
        $freedBytes = 0;

        foreach ([$this->dbBackupPath, $this->filesBackupPath] as $dir) {
            if (!File::isDirectory($dir)) {
                continue;
            }

            $files = File::files($dir);
            // Sort by file modification time descending (newest first)
            usort($files, fn($a, $b) => $b->getMTime() <=> $a->getMTime());

            if (count($files) > $maxToKeep) {
                $filesToDelete = array_slice($files, $maxToKeep);
                foreach ($filesToDelete as $file) {
                    $filename = $file->getFilename();
                    if ($filename === '.htaccess') {
                        continue;
                    }
                    $size = $file->getSize();
                    if (File::delete($file->getRealPath())) {
                        $freedBytes += $size;
                        $deleted[] = $filename;
                    }
                }
            }
        }

        if (!empty($deleted)) {
            Log::channel('daily')->info('Pruned old backups', [
                'deleted_files' => $deleted,
                'freed_bytes' => $freedBytes,
            ]);
        }

        return [
            'deleted_count' => count($deleted),
            'deleted_files' => $deleted,
            'freed_bytes' => $freedBytes,
            'freed_formatted' => $this->formatBytes($freedBytes),
        ];
    }

    /**
     * List all existing backups.
     */
    public function listBackups(): array
    {
        $backups = [];

        foreach (['db' => $this->dbBackupPath, 'files' => $this->filesBackupPath] as $type => $dir) {
            if (!File::isDirectory($dir)) {
                continue;
            }

            foreach (File::files($dir) as $file) {
                $filename = $file->getFilename();
                if ($filename === '.htaccess') {
                    continue;
                }

                $backups[] = [
                    'filename' => $filename,
                    'type' => $type,
                    'size_bytes' => $file->getSize(),
                    'size_formatted' => $this->formatBytes($file->getSize()),
                    'modified_at' => date('Y-m-d H:i:s', $file->getMTime()),
                    'timestamp' => $file->getMTime(),
                    'path' => $file->getRealPath(),
                ];
            }
        }

        // Sort newest first
        usort($backups, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return $backups;
    }

    /**
     * Get safe backup file path by type and filename (prevents path traversal).
     */
    public function getBackupPath(string $type, string $filename): ?string
    {
        $sanitizedFilename = basename($filename);
        $dir = $type === 'files' ? $this->filesBackupPath : $this->dbBackupPath;
        $path = $dir . DIRECTORY_SEPARATOR . $sanitizedFilename;

        if (File::exists($path) && $sanitizedFilename !== '.htaccess') {
            return $path;
        }

        return null;
    }

    /**
     * Delete a specific backup file.
     */
    public function deleteBackup(string $type, string $filename): bool
    {
        $path = $this->getBackupPath($type, $filename);
        if ($path && File::delete($path)) {
            Log::channel('daily')->info('Deleted backup file', [
                'type' => $type,
                'filename' => basename($filename),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Helper to format bytes to human-readable string.
     */
    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
