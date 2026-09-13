<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ScalabilityBackupDisasterRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $student;
    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin@marketianmind.test',
        ]);

        $this->student = User::factory()->create([
            'role' => 'student',
            'email' => 'student@marketianmind.test',
        ]);

        $this->backupService = app(BackupService::class);
    }

    protected function tearDown(): void
    {
        // Clean up any test backups created in storage/app/backups
        $dbPath = storage_path('app/backups/db');
        $filesPath = storage_path('app/backups/files');

        if (File::isDirectory($dbPath)) {
            foreach (File::files($dbPath) as $file) {
                if ($file->getFilename() !== '.htaccess') {
                    File::delete($file->getRealPath());
                }
            }
        }

        if (File::isDirectory($filesPath)) {
            foreach (File::files($filesPath) as $file) {
                if ($file->getFilename() !== '.htaccess') {
                    File::delete($file->getRealPath());
                }
            }
        }

        parent::tearDown();
    }

    public function test_public_health_endpoint_returns_ok_and_json(): void
    {
        $response = $this->getJson('/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'app' => 'Marketian Mind',
            ])
            ->assertJsonStructure([
                'status',
                'app',
                'timestamp',
            ]);
    }

    public function test_public_health_endpoint_leaks_no_credentials_or_passwords(): void
    {
        $response = $this->getJson('/health');
        $content = $response->getContent();

        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('root', $content);
        $this->assertStringNotContainsString('127.0.0.1', $content);
        $this->assertStringNotContainsString('database', strtolower($content));
        $this->assertStringNotContainsString('mysql', strtolower($content));
        $this->assertStringNotContainsString('sqlite', strtolower($content));
        $this->assertStringNotContainsString('env', strtolower($content));
    }

    public function test_admin_system_health_requires_authentication(): void
    {
        $response = $this->get(route('admin.system.health'));
        $response->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_admin_system_health(): void
    {
        $response = $this->actingAs($this->student)->get(route('admin.system.health'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_system_health_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.system.health'));

        $response->assertStatus(200)
            ->assertSee('System Health &amp; Backups', false)
            ->assertSee('Database Health')
            ->assertSee('Storage Disk')
            ->assertSee('Cache Subsystem')
            ->assertSee('Hostinger Shared Hosting Runtime Limits');
    }

    public function test_backup_service_can_create_database_backup(): void
    {
        $result = $this->backupService->backupDatabase('test_db_backup.sql');

        $this->assertTrue($result['success']);
        $this->assertEquals('test_db_backup.sql', $result['filename']);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, filesize($result['path']));
    }

    public function test_backup_service_can_create_files_backup(): void
    {
        $result = $this->backupService->backupFiles('test_files_backup.tar.gz');

        $this->assertTrue($result['success']);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(0, filesize($result['path']));
    }

    public function test_backup_service_prunes_old_backups_according_to_retention(): void
    {
        $dbDir = storage_path('app/backups/db');
        File::ensureDirectoryExists($dbDir);

        // Create 5 dummy backups with staggered file modification times
        for ($i = 1; $i <= 5; $i++) {
            $filePath = $dbDir . DIRECTORY_SEPARATOR . "dummy_backup_{$i}.sql";
            File::put($filePath, "DUMMY BACKUP DATA {$i}");
            touch($filePath, time() - (86400 * (6 - $i))); // 5 days ago to 1 day ago
        }

        $this->assertCount(5, array_filter(File::files($dbDir), fn($f) => $f->getFilename() !== '.htaccess'));

        // Prune keeping only 2
        $pruneResult = $this->backupService->pruneOldBackups(2, 2);

        $this->assertEquals(3, $pruneResult['deleted_count']);
        $remaining = array_filter(File::files($dbDir), fn($f) => $f->getFilename() !== '.htaccess');
        $this->assertCount(2, $remaining);
    }

    public function test_artisan_backup_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('app:backup', [
            '--type' => 'files',
            '--prune' => true,
        ]);

        $this->assertEquals(0, $exitCode);
    }

    public function test_admin_can_trigger_backup_via_post(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.system.backups.run'), [
            'type' => 'files',
        ]);

        if ($response->getSession()->has('error')) {
            $this->fail('Backup failed with: ' . $response->getSession()->get('error'));
        }

        $response->assertRedirect(route('admin.system.health'))
            ->assertSessionHas('success');
    }

    public function test_admin_can_download_and_delete_backup(): void
    {
        $backupResult = $this->backupService->backupFiles('downloadable_backup.tar.gz');
        $this->assertTrue($backupResult['success']);

        // Download test
        $downloadResponse = $this->actingAs($this->admin)->get(
            route('admin.system.backups.download', ['type' => 'files', 'filename' => 'downloadable_backup.tar.gz'])
        );
        $downloadResponse->assertStatus(200);

        // Delete test
        $deleteResponse = $this->actingAs($this->admin)->delete(
            route('admin.system.backups.delete', ['type' => 'files', 'filename' => 'downloadable_backup.tar.gz'])
        );
        $deleteResponse->assertRedirect(route('admin.system.health'))
            ->assertSessionHas('success');

        $this->assertFileDoesNotExist($backupResult['path']);
    }

    public function test_backup_download_blocks_path_traversal_attempts(): void
    {
        $response = $this->actingAs($this->admin)->get(
            route('admin.system.backups.download', ['type' => 'files', 'filename' => '../../.env'])
        );

        $response->assertStatus(404);
    }

    public function test_critical_payment_and_enrollment_transaction_atomicity(): void
    {
        $course = Course::create([
            'title' => 'Advanced Digital Marketing',
            'slug' => 'advanced-digital-marketing-' . uniqid(),
            'description' => 'Comprehensive marketing mastery.',
            'short_description' => 'Comprehensive marketing mastery.',
            'price' => 199900,
            'status' => 'published',
            'level' => 'all_levels',
            'is_featured' => true,
        ]);

        $initialOrderCount = Order::count();

        // Simulate failed transactional write
        try {
            DB::transaction(function () use ($course) {
                Order::create([
                    'user_id' => $this->student->id,
                    'course_id' => $course->id,
                    'order_number' => 'ORD-TEST-FAIL-01',
                    'amount' => $course->price,
                    'status' => 'pending',
                ]);

                // Simulate unexpected failure inside atomic block
                throw new \RuntimeException('Simulated payment exception');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Verify order was cleanly rolled back
        $this->assertEquals($initialOrderCount, Order::count());
    }
}
