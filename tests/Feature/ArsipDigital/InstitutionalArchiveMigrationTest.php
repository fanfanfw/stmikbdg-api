<?php

namespace Tests\Feature\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\Distribution;
use App\Models\ArsipDigital\InstitutionalArchive;
use App\Models\ArsipDigital\InstitutionalUnit;
use App\Services\ArsipDigital\InstitutionalArchiveService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDOException;
use RuntimeException;
use Tests\TestCase;

class InstitutionalArchiveMigrationTest extends TestCase
{
    private string $connection;

    private string $maintenanceConnection = 'arsip_migration_maintenance';

    private string $database;

    private string $activeDatabase;

    private array $originalConfig;

    private ?string $storageRoot = null;

    private array $childPids = [];

    private array $temporarySignals = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = config('myconfig.database.first_connection');
        $this->originalConfig = [
            'connection' => config('database.connections.'.$this->connection),
            'first_connection' => config('myconfig.database.first_connection'),
            's3' => config('filesystems.disks.s3'),
        ];
        $active = $this->originalConfig['connection'];
        if (($active['driver'] ?? null) !== 'pgsql') {
            throw new RuntimeException('Institutional archive migration tests require configured first PostgreSQL connection.');
        }

        $this->activeDatabase = $active['database'];
        $this->database = 'arsip_migration_test_'.bin2hex(random_bytes(8));
        if ($this->database === $this->activeDatabase) {
            throw new RuntimeException('Temporary migration database must differ from active application database.');
        }

        $maintenance = $active;
        $maintenance['url'] = null;
        $maintenance['database'] = 'postgres';
        config(['database.connections.'.$this->maintenanceConnection => $maintenance]);
        DB::purge($this->maintenanceConnection);

        try {
            DB::connection($this->maintenanceConnection)->statement('CREATE DATABASE "'.$this->database.'"');
        } catch (QueryException $exception) {
            throw new RuntimeException('Unable to provision temporary PostgreSQL database using existing configured credentials: '.$exception->getCode(), 0, $exception);
        }

        config(['database.connections.'.$this->connection.'.url' => null]);
        config(['database.connections.'.$this->connection.'.database' => $this->database]);
        DB::purge($this->connection);
        $this->assertNotSame($active['database'], DB::connection($this->connection)->getDatabaseName());
    }

    protected function tearDown(): void
    {
        $cleanupFailure = null;
        try {
            $this->terminateChildren();
            foreach ($this->temporarySignals as $path) {
                @unlink($path);
            }
            if ($this->storageRoot !== null) {
                $this->removeDirectory($this->storageRoot);
            }

            DB::disconnect($this->connection);
            DB::purge($this->connection);
            if (isset($this->database)) {
                if (! str_starts_with($this->database, 'arsip_migration_test_') || $this->database === $this->activeDatabase) {
                    throw new RuntimeException('Refusing unsafe temporary PostgreSQL database cleanup.');
                }
                $maintenance = DB::connection($this->maintenanceConnection);
                $maintenance->statement('SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = ? AND pid <> pg_backend_pid()', [$this->database]);
                $maintenance->statement('DROP DATABASE "'.$this->database.'"');
            }
        } catch (\Throwable $exception) {
            $cleanupFailure = $exception;
        } finally {
            DB::purge($this->maintenanceConnection);
            config([
                'database.connections.'.$this->connection => $this->originalConfig['connection'],
                'myconfig.database.first_connection' => $this->originalConfig['first_connection'],
                'filesystems.disks.s3' => $this->originalConfig['s3'],
            ]);
            Storage::forgetDisk('s3');
            parent::tearDown();
        }

        if ($cleanupFailure) {
            throw new RuntimeException('Failed to clean temporary PostgreSQL test resources: '.$cleanupFailure->getCode(), 0, $cleanupFailure);
        }
    }

    public function test_fresh_migration_has_expected_postgresql_schema_and_preserves_old_values(): void
    {
        $this->migrateFresh();
        $db = DB::connection($this->connection);

        foreach (['institutional_units', 'institutional_archives'] as $table) {
            $this->assertTrue($db->getSchemaBuilder()->hasTable('arsip_digital.'.$table));
        }
        foreach (['categories_category_type_check', 'files_source_type_check', 'files_institutional_source_check', 'distributions_source_exclusivity_check', 'institutional_archives_current_file_fk'] as $name) {
            $this->assertDatabaseObjectExists('constraint', $name);
        }
        foreach (['categories_institutional_name_active_unique', 'files_institutional_archive_current_unique', 'institutional_archives_document_number_unique'] as $name) {
            $this->assertDatabaseObjectExists('index', $name);
        }

        foreach (['personal', 'official', 'distribution', 'institutional'] as $type) {
            $db->table('arsip_digital.categories')->insert($this->category($type, $type));
        }
        foreach (['personal', 'request', 'distribution', 'admin_upload', 'official'] as $type) {
            $db->table('arsip_digital.files')->insert($this->file($type));
        }
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.categories')->insert($this->category('invalid', 'invalid')));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.files')->insert($this->file('invalid')));
    }

    public function test_upgrade_from_pre_phase_one_schema_preserves_existing_data(): void
    {
        $migrationFiles = glob(database_path('migrations/*.php'));
        sort($migrationFiles);
        foreach ($migrationFiles as $file) {
            if (basename($file) === '2026_08_02_000012_create_institutional_archive_foundation.php') {
                break;
            }
            $this->assertSame(0, Artisan::call('migrate', [
                '--database' => $this->connection,
                '--path' => 'database/migrations/'.basename($file),
                '--realpath' => false,
                '--force' => true,
            ]));
        }

        $db = DB::connection($this->connection);
        foreach (['personal', 'official', 'distribution'] as $type) {
            $db->table('arsip_digital.categories')->insert($this->category($type, 'Existing '.$type));
        }
        $fileIds = [];
        foreach (['personal', 'request', 'distribution', 'admin_upload', 'official'] as $type) {
            $file = $this->file($type);
            unset($file['institutional_archive_id']);
            $fileIds[$type] = $db->table('arsip_digital.files')->insertGetId($file, 'file_id');
        }
        $officialId = $db->table('arsip_digital.official_documents')->insertGetId([
            'document_type' => 'transcript', 'document_number' => 'UPGRADE-001', 'subject_user_id' => 1,
            'subject_mhs_id' => 1, 'subject_identifier' => '1', 'subject_name_snapshot' => 'Existing Student',
            'academic_snapshot' => '{}', 'snapshot_captured_at' => now(), 'template_version' => 'v1',
            'file_id' => $fileIds['official'], 'file_checksum_sha256' => str_repeat('a', 64), 'issued_by_user_id' => 1,
            'issued_at' => now(), 'signer_user_id' => 1, 'signer_name_snapshot' => 'Existing Signer',
            'signer_title_snapshot' => 'Head',
        ], 'official_document_id');
        $distributionId = $db->table('arsip_digital.distributions')->insertGetId($this->distribution([
            'official_document_id' => $officialId,
        ]), 'distribution_id');

        $this->assertFalse($db->getSchemaBuilder()->hasTable('arsip_digital.institutional_archives'));
        $this->assertSame(0, Artisan::call('migrate', [
            '--database' => $this->connection,
            '--path' => 'database/migrations/2026_08_02_000012_create_institutional_archive_foundation.php',
            '--force' => true,
        ]));

        foreach (['personal', 'official', 'distribution'] as $type) {
            $this->assertTrue($db->table('arsip_digital.categories')->where('name', 'Existing '.$type)->where('category_type', $type)->exists());
        }
        foreach ($fileIds as $type => $id) {
            $this->assertTrue($db->table('arsip_digital.files')->where('file_id', $id)->where('source_type', $type)->whereNull('institutional_archive_id')->exists());
        }
        $this->assertTrue($db->table('arsip_digital.distributions')->where('distribution_id', $distributionId)->where('official_document_id', $officialId)->whereNull('institutional_archive_id')->whereNull('source_file_id')->exists());
    }

    public function test_number_constraints_normalize_whitespace_and_keep_soft_deleted_unique(): void
    {
        $this->migrateFresh();
        $db = DB::connection($this->connection);
        $unitA = $this->unit('A');
        $unitB = $this->unit('B');

        $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA));
        $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA));
        $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA, [
            'document_number' => " 001 / BAAK\t/ 2026 ",
            'document_year' => 2026,
            'status' => 'deleted',
            'deleted_at' => now(),
            'deleted_by_user_id' => 1,
            'delete_reason' => 'Historical record',
        ]));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA, ['document_number' => '001/baak/2026', 'document_year' => 2026])));
        $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitB, ['document_number' => '001/BAAK/2026', 'document_year' => 2026]));
        $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA, ['document_number' => '001/BAAK/2026', 'document_year' => 2027]));
        $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA, ['document_number' => 'DATE-ONLY', 'document_date' => '2026-01-02']));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA, ['document_number' => 'MISSING'])));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->insert($this->archive($unitA, ['document_number' => 'MISMATCH', 'document_date' => '2026-01-02', 'document_year' => 2025])));
    }

    public function test_file_current_archive_and_distribution_invariants(): void
    {
        $this->migrateFresh();
        $db = DB::connection($this->connection);
        $unit = $this->unit('A');
        $archiveA = $this->archiveId($unit);
        $archiveB = $this->archiveId($unit);
        $fileA = $this->fileId('institutional', $archiveA);

        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.files')->insert($this->file('institutional')));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.files')->insert($this->file('personal', $archiveA)));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.files')->insert($this->file('institutional', 999999)));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.files')->insert($this->file('institutional', $archiveA)));

        $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archiveA)->update(['current_file_id' => $fileA]);
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archiveB)->update(['current_file_id' => $fileA]), true);
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.files')->where('file_id', $fileA)->update(['is_current' => false]));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.files')->where('file_id', $fileA)->delete());
        $this->assertTrue($db->table('arsip_digital.files')->where('file_id', $fileA)->exists());
        $this->assertSame($fileA, $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archiveA)->value('current_file_id'));

        $db->table('arsip_digital.distributions')->insert($this->distribution(['institutional_archive_id' => $archiveA, 'source_file_id' => $fileA]));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.distributions')->insert($this->distribution(['institutional_archive_id' => $archiveA])));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.distributions')->insert($this->distribution(['source_file_id' => $fileA])));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.distributions')->insert($this->distribution(['institutional_archive_id' => $archiveB, 'source_file_id' => $fileA])));

        foreach ([['is_current' => false], ['status' => 'replaced'], ['deleted_at' => now()]] as $invalidState) {
            $invalidArchive = $this->archiveId($unit);
            $invalidFile = $this->fileId('institutional', $invalidArchive);
            $db->table('arsip_digital.files')->where('file_id', $invalidFile)->update($invalidState);
            $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.distributions')->insert($this->distribution([
                'institutional_archive_id' => $invalidArchive,
                'source_file_id' => $invalidFile,
            ])));
        }

        $officialFile = $this->fileId('official');
        $officialId = $db->table('arsip_digital.official_documents')->insertGetId([
            'document_type' => 'transcript', 'document_number' => fake()->uuid(), 'subject_user_id' => 1,
            'subject_mhs_id' => 1, 'subject_identifier' => '1', 'subject_name_snapshot' => 'Student',
            'academic_snapshot' => '{}', 'snapshot_captured_at' => now(), 'template_version' => 'v1',
            'file_id' => $officialFile, 'file_checksum_sha256' => str_repeat('a', 64), 'issued_by_user_id' => 1,
            'issued_at' => now(), 'signer_user_id' => 1, 'signer_name_snapshot' => 'Signer', 'signer_title_snapshot' => 'Head',
        ], 'official_document_id');
        $db->table('arsip_digital.distributions')->insert($this->distribution(['official_document_id' => $officialId]));
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.distributions')->insert($this->distribution(['official_document_id' => $officialId, 'institutional_archive_id' => $archiveA, 'source_file_id' => $fileA])));
    }

    public function test_archive_lifecycle_check_accepts_complete_states_and_rejects_partial_states(): void
    {
        $this->migrateFresh();
        $db = DB::connection($this->connection);
        $unit = $this->unit('Lifecycle');

        $active = $this->archiveId($unit);
        $deleted = $this->archiveId($unit);
        $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $deleted)->update([
            'status' => 'deleted',
            'deleted_at' => now(),
            'deleted_by_user_id' => 7,
            'delete_reason' => 'Retention decision',
        ]);
        $this->assertSame('active', $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $active)->value('status'));
        $this->assertSame('deleted', $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $deleted)->value('status'));

        foreach ([
            ['status' => 'active', 'deleted_at' => now()],
            ['status' => 'active', 'deleted_by_user_id' => 7],
            ['status' => 'active', 'delete_reason' => 'reason'],
            ['status' => 'deleted'],
            ['status' => 'deleted', 'deleted_at' => now(), 'deleted_by_user_id' => 7, 'delete_reason' => '   '],
            ['status' => 'deleted', 'deleted_at' => now(), 'delete_reason' => 'reason'],
        ] as $invalid) {
            $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->insert($this->archive($unit, $invalid)));
        }
    }

    public function test_version_transition_allows_new_current_but_rejects_invalid_current_file_reference(): void
    {
        $this->migrateFresh();
        $db = DB::connection($this->connection);
        $archive = $this->archiveId($this->unit('Versioning'));
        $old = $this->fileId('institutional', $archive);
        $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->update(['current_file_id' => $old]);

        $db->transaction(function () use ($db, $archive, $old): void {
            $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->update(['current_file_id' => null]);
            $db->table('arsip_digital.files')->where('file_id', $old)->update(['is_current' => false, 'status' => 'replaced']);
            $new = $this->fileId('institutional', $archive);
            $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->update(['current_file_id' => $new]);
        });
        $this->assertSame(1, $db->table('arsip_digital.files')->where('institutional_archive_id', $archive)->where('is_current', true)->where('status', 'active')->whereNull('deleted_at')->count());

        $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->update(['current_file_id' => null]);
        $db->table('arsip_digital.files')->where('institutional_archive_id', $archive)->where('status', 'active')->update(['is_current' => false]);
        $replacedCurrent = $this->fileId('institutional', $archive);
        $db->table('arsip_digital.files')->where('file_id', $replacedCurrent)->update(['status' => 'replaced']);
        $this->fileId('institutional', $archive);
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->update(['current_file_id' => $replacedCurrent]), true);

        $db->table('arsip_digital.files')->where('institutional_archive_id', $archive)->where('status', 'active')->update(['is_current' => false]);
        $deletedCurrent = $this->fileId('institutional', $archive);
        $db->table('arsip_digital.files')->where('file_id', $deletedCurrent)->update(['deleted_at' => now()]);
        $this->fileId('institutional', $archive);
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->update(['current_file_id' => $deletedCurrent]), true);
    }

    public function test_upgrade_rollback_fail_closed_clean_rollback_and_remigrate(): void
    {
        $this->migrateFresh();
        $db = DB::connection($this->connection);
        $db->table('arsip_digital.categories')->insert($this->category('official', 'Preserved'));
        $db->table('arsip_digital.files')->insert($this->file('official'));
        $unit = $this->unit('A');
        $archive = $this->archiveId($unit);

        try {
            Artisan::call('migrate:rollback', ['--database' => $this->connection, '--step' => 1, '--force' => true]);
            $this->fail('Rollback must fail while institutional data exists.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('institutional data exists', $exception->getMessage());
        }
        $this->assertTrue($db->getSchemaBuilder()->hasTable('arsip_digital.institutional_archives'));
        $this->assertSame($archive, $db->table('arsip_digital.institutional_archives')->value('institutional_archive_id'));
        $this->assertTrue($db->table('arsip_digital.categories')->where('name', 'Preserved')->exists());

        $db->table('arsip_digital.institutional_archives')->delete();
        $db->table('arsip_digital.institutional_units')->delete();
        $this->assertSame(0, Artisan::call('migrate:rollback', ['--database' => $this->connection, '--step' => 1, '--force' => true]));
        $this->assertFalse($db->getSchemaBuilder()->hasTable('arsip_digital.institutional_archives'));
        $this->assertTrue($db->table('arsip_digital.categories')->where('name', 'Preserved')->exists());
        $this->assertSame(0, Artisan::call('migrate', ['--database' => $this->connection, '--force' => true]));
        $this->assertTrue($db->getSchemaBuilder()->hasTable('arsip_digital.institutional_archives'));
    }

    public function test_real_postgresql_service_version_waits_for_archive_lock_and_serializes_two_contenders(): void
    {
        $this->migrateFresh();
        $this->configureSharedLocalStorage();
        $archive = $this->seedArchiveThroughService();
        DB::disconnect($this->connection);
        DB::disconnect($this->maintenanceConnection);
        $locker = null;
        $signals = [];
        $gates = [];
        $children = [];
        try {
            foreach ([2, 3] as $version) {
                $signal = sys_get_temp_dir().'/arsip_version_'.bin2hex(random_bytes(8));
                $gate = $signal.'.gate';
                $signals[] = $signal;
                $gates[] = $gate;
                $this->temporarySignals[] = $signal;
                $this->temporarySignals[] = $gate;
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        while (! is_file($gate)) {
                            usleep(10000);
                        }
                        DB::purge($this->connection);
                        app(InstitutionalArchiveService::class)->uploadVersion($archive, UploadedFile::fake()->createWithContent("v$version.pdf", "%PDF-1.4 v$version"), "Version $version", $this->actor());
                        file_put_contents($signal, 'ok');
                        exit(0);
                    } catch (\Throwable $exception) {
                        file_put_contents($signal, 'error:'.$exception::class);
                        exit(1);
                    }
                }
                $this->childPids[$pid] = true;
                $children[] = [$pid, $signal];
            }
            $locker = DB::connection($this->connection);
            $locker->beginTransaction();
            $locker->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->lockForUpdate()->first();
            foreach ($gates as $gate) {
                touch($gate);
            }
            $blocked = false;
            $deadline = microtime(true) + 5;
            do {
                usleep(50000);
                $blocked = DB::connection($this->maintenanceConnection)->table('pg_stat_activity')->where('datname', $this->database)->where('wait_event_type', 'Lock')->count() >= 2;
            } while (! $blocked && microtime(true) < $deadline);
            $this->assertTrue($blocked, 'Two production service contenders never exposed PostgreSQL lock waits.');
            foreach ($signals as $signal) {
                $this->assertFileDoesNotExist($signal);
            }
            $locker->commit();
            foreach ($children as [$pid, $signal]) {
                $this->waitForChild($pid, $signal);
            }
        } finally {
            if ($locker && $locker->transactionLevel() > 0) {
                $locker->rollBack();
            }
            foreach ([...$signals, ...$gates] as $signal) {
                @unlink($signal);
            }
        }

        $files = DB::connection($this->connection)->table('arsip_digital.files')->where('institutional_archive_id', $archive)->orderBy('version_number')->get();
        $this->assertSame([1, 2, 3], $files->pluck('version_number')->all());
        $this->assertSame(1, $files->where('is_current', true)->where('status', 'active')->count());
        $this->assertSame(2, $files->where('is_current', false)->where('status', 'replaced')->count());
        $this->assertSame(3, $files->where('file_id', DB::connection($this->connection)->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->value('current_file_id'))->first()->version_number);
        $this->assertSame(2, DB::connection($this->connection)->table('arsip_digital.audit_logs')->where('action', 'institutional_archive.file_version_uploaded')->count());
        $this->assertCount(3, Storage::disk('s3')->allFiles());
        foreach ($files as $file) {
            $this->assertTrue(Storage::disk($file->storage_disk)->exists($file->storage_path), "Version {$file->version_number} object missing.");
        }
        $this->assertDatabaseObjectExists('index', 'files_institutional_archive_current_unique');
    }

    public function test_real_postgresql_service_audit_failure_rolls_back_transition_and_cleans_new_object(): void
    {
        $this->migrateFresh();
        $this->configureSharedLocalStorage();
        $archive = $this->seedArchiveThroughService();
        $before = Storage::disk('s3')->allFiles();
        DB::connection($this->connection)->unprepared("CREATE FUNCTION arsip_digital.fail_version_audit() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF NEW.action = 'institutional_archive.file_version_uploaded' THEN RAISE EXCEPTION 'audit failed'; END IF; RETURN NEW; END $$; CREATE TRIGGER fail_version_audit BEFORE INSERT ON arsip_digital.audit_logs FOR EACH ROW EXECUTE FUNCTION arsip_digital.fail_version_audit()");

        try {
            app(InstitutionalArchiveService::class)->uploadVersion($archive, UploadedFile::fake()->createWithContent('failed.pdf', '%PDF-1.4 failed'), 'Failure', $this->actor());
            $this->fail('Audit failure must escape production service transaction.');
        } catch (QueryException $exception) {
            $this->assertSame('P0001', $exception->errorInfo[0] ?? $exception->getCode());
        }

        $files = DB::connection($this->connection)->table('arsip_digital.files')->where('institutional_archive_id', $archive)->get();
        $this->assertCount(1, $files);
        $this->assertTrue((bool) $files->first()->is_current);
        $this->assertSame('active', $files->first()->status);
        $this->assertSame($files->first()->file_id, DB::connection($this->connection)->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archive)->value('current_file_id'));
        $this->assertSame(0, DB::connection($this->connection)->table('arsip_digital.audit_logs')->where('action', 'institutional_archive.file_version_uploaded')->count());
        $this->assertSame($before, Storage::disk('s3')->allFiles());
        $this->assertTrue(Storage::disk($files->first()->storage_disk)->exists($files->first()->storage_path));
    }

    public function test_real_postgresql_phase_five_delete_restore_audit_failures_rollback_and_number_stays_reserved(): void
    {
        $this->migrateFresh();
        $this->configureSharedLocalStorage();
        $archiveId = $this->seedArchiveThroughService();
        $service = app(InstitutionalArchiveService::class);
        $db = DB::connection($this->connection);
        $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archiveId)->update(['document_number' => 'PHASE5-001', 'document_year' => 2026]);
        $db->unprepared("CREATE FUNCTION arsip_digital.fail_lifecycle_audit() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF NEW.action IN ('institutional_archive.deleted','institutional_archive.restored') THEN RAISE EXCEPTION 'audit failed'; END IF; RETURN NEW; END $$; CREATE TRIGGER fail_lifecycle_audit BEFORE INSERT ON arsip_digital.audit_logs FOR EACH ROW EXECUTE FUNCTION arsip_digital.fail_lifecycle_audit()");
        try {
            $service->delete($archiveId, 'Fail', $this->actor());
            $this->fail('Delete audit failure must rollback.');
        } catch (QueryException $e) {
            $this->assertSame('P0001', $e->errorInfo[0]);
        }
        $this->assertSame('active', $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archiveId)->value('status'));
        $db->unprepared('DROP TRIGGER fail_lifecycle_audit ON arsip_digital.audit_logs; DROP FUNCTION arsip_digital.fail_lifecycle_audit()');
        $service->delete($archiveId, 'Historical', $this->actor());
        $row = $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archiveId)->first();
        $this->expectDatabaseViolation(fn () => $db->table('arsip_digital.institutional_archives')->insert($this->archive($row->unit_id, ['document_number' => $row->document_number, 'document_year' => $row->document_year])));
        $db->unprepared("CREATE FUNCTION arsip_digital.fail_restore_audit() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN IF NEW.action = 'institutional_archive.restored' THEN RAISE EXCEPTION 'audit failed'; END IF; RETURN NEW; END $$; CREATE TRIGGER fail_restore_audit BEFORE INSERT ON arsip_digital.audit_logs FOR EACH ROW EXECUTE FUNCTION arsip_digital.fail_restore_audit()");
        try {
            $service->restore($archiveId, $this->actor());
            $this->fail('Restore audit failure must rollback.');
        } catch (QueryException $e) {
            $this->assertSame('P0001', $e->errorInfo[0]);
        }
        $this->assertSame('deleted', $db->table('arsip_digital.institutional_archives')->where('institutional_archive_id', $archiveId)->value('status'));
    }

    public function test_models_expose_foundation_relations_and_casts(): void
    {
        $this->assertSame('boolean', (new InstitutionalUnit)->getCasts()['is_active']);
        $archive = new InstitutionalArchive;
        $this->assertSame('array', $archive->getCasts()['tags']);
        $this->assertSame('date', $archive->getCasts()['document_date']);
        $this->assertSame(InstitutionalUnit::class, get_class($archive->unit()->getRelated()));
        $this->assertSame(ArchiveFile::class, get_class($archive->currentFile()->getRelated()));
        $distribution = new Distribution;
        $this->assertSame('datetime', $distribution->getCasts()['expires_at']);
        $this->assertSame(InstitutionalArchive::class, get_class($distribution->institutionalArchive()->getRelated()));
    }

    private function seedArchiveThroughService(): int
    {
        config(['myconfig.database.first_connection' => $this->connection]);
        $db = DB::connection($this->connection);
        $db->table('arsip_digital.settings')->where('key', 'archive_defaults')->update(['value' => json_encode(['default_max_file_size_mb' => 10, 'default_allowed_extensions' => ['pdf'], 'storage_disk' => 's3'])]);
        $unit = $db->table('arsip_digital.institutional_units')->insertGetId(['name' => 'Concurrency', 'created_by_user_id' => 1], 'unit_id');

        return app(InstitutionalArchiveService::class)->create(UploadedFile::fake()->createWithContent('v1.pdf', '%PDF-1.4 v1'), ['title' => 'Concurrent archive', 'unit_id' => $unit], $this->actor())->institutional_archive_id;
    }

    private function configureSharedLocalStorage(): void
    {
        $this->storageRoot = sys_get_temp_dir().'/arsip_storage_'.bin2hex(random_bytes(8));
        if (! mkdir($this->storageRoot, 0700, true) && ! is_dir($this->storageRoot)) {
            throw new RuntimeException('Unable to create temporary storage root.');
        }
        config(['filesystems.disks.s3' => ['driver' => 'local', 'root' => $this->storageRoot, 'throw' => true]]);
        Storage::forgetDisk('s3');
    }

    private function actor(): object
    {
        return (object) ['id' => 1, 'kd_user' => 'ADM-1', 'name' => 'Admin'];
    }

    private function waitForChild(int $pid, string $signal): void
    {
        $deadline = microtime(true) + 10;
        do {
            $result = pcntl_waitpid($pid, $status, WNOHANG);
            if ($result === $pid) {
                unset($this->childPids[$pid]);
                $this->assertSame(0, pcntl_wexitstatus($status), is_file($signal) ? file_get_contents($signal) : 'Child exited without signal.');
                $this->assertSame('ok', file_get_contents($signal));

                return;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);
        $this->stopChild($pid);
        $this->fail('Version contender exceeded 10 second timeout.');
    }

    private function terminateChildren(): void
    {
        foreach (array_keys($this->childPids) as $pid) {
            $this->stopChild($pid);
        }
    }

    private function stopChild(int $pid): void
    {
        if (! isset($this->childPids[$pid])) {
            return;
        }
        posix_kill($pid, SIGTERM);
        $deadline = microtime(true) + 1;
        do {
            if (pcntl_waitpid($pid, $status, WNOHANG) === $pid) {
                unset($this->childPids[$pid]);

                return;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);
        posix_kill($pid, SIGKILL);
        pcntl_waitpid($pid, $status);
        unset($this->childPids[$pid]);
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }

    private function migrateFresh(): void
    {
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--database' => $this->connection, '--force' => true]));
    }

    private function unit(string $name): int
    {
        return DB::connection($this->connection)->table('arsip_digital.institutional_units')->insertGetId(['name' => $name, 'created_by_user_id' => 1], 'unit_id');
    }

    private function archiveId(int $unit): int
    {
        return DB::connection($this->connection)->table('arsip_digital.institutional_archives')->insertGetId($this->archive($unit), 'institutional_archive_id');
    }

    private function fileId(string $type, ?int $archive = null): int
    {
        return DB::connection($this->connection)->table('arsip_digital.files')->insertGetId($this->file($type, $archive), 'file_id');
    }

    private function archive(int $unit, array $values = []): array
    {
        return array_merge(['archive_uuid' => fake()->uuid(), 'title' => 'Archive', 'unit_id' => $unit, 'created_by_user_id' => 1], $values);
    }

    private function category(string $type, string $name): array
    {
        return ['category_type' => $type, 'name' => $name, 'created_by_user_id' => 1, 'created_by_role' => 'admin'];
    }

    private function file(string $type, ?int $archive = null): array
    {
        return ['owner_user_id' => 1, 'owner_role' => 'admin', 'owner_identifier' => 'admin-1', 'uploaded_by_user_id' => 1,
            'uploaded_by_role' => 'admin', 'source_type' => $type, 'original_filename' => 'a.pdf', 'display_filename' => 'a.pdf',
            'storage_disk' => 's3', 'storage_path' => fake()->uuid().'.pdf', 'extension' => 'pdf', 'file_size_bytes' => 1,
            'version_group_uuid' => fake()->uuid(), 'institutional_archive_id' => $archive];
    }

    private function distribution(array $values): array
    {
        return array_merge(['title' => 'Distribution', 'target_role' => 'mahasiswa', 'scope_type' => 'specific', 'created_by_user_id' => 1], $values);
    }

    private function expectDatabaseViolation(callable $operation, bool $deferred = false): void
    {
        try {
            if ($deferred) {
                DB::connection($this->connection)->transaction($operation);
            } else {
                $operation();
            }
            $this->fail('Expected PostgreSQL constraint violation.');
        } catch (QueryException|PDOException) {
        }
    }

    private function assertDatabaseObjectExists(string $type, string $name): void
    {
        $catalog = $type === 'index' ? 'pg_indexes' : 'information_schema.table_constraints';
        $column = $type === 'index' ? 'indexname' : 'constraint_name';
        $this->assertTrue(DB::connection($this->connection)->table($catalog)->where($column, $name)->exists(), "$type $name missing");
    }
}
