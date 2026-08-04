<?php

namespace Tests\Feature\ArsipDigital;

use App\Models\Users\UserView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InstitutionalArchiveDistributionTest extends ArsipDigitalFeatureTestCase
{
    private int $archiveId;

    private int $fileId;

    protected function setUp(): void
    {
        parent::setUp();
        $unit = DB::table('arsip_digital.institutional_units')->insertGetId(['name' => 'Akademik', 'is_active' => true, 'created_by_user_id' => 1]);
        $archive = $this->actingAsAdmin()->post('/api/arsip-digital/admin/institutional-archives', [
            'file' => $this->pdfUpload('lembaga.pdf', '%PDF-1.4 institutional'), 'title' => 'Arsip Lembaga', 'unit_id' => $unit,
        ], ['Accept' => 'application/json'])->assertCreated()->json('data.archive');
        $this->archiveId = $archive['institutional_archive_id'];
        $this->fileId = $archive['current_file_id'];
    }

    public function test_management_requires_admin_and_is_shared_between_admins(): void
    {
        $payload = $this->targets();
        foreach ([$this->actingAsMahasiswa(), $this->actingAsDosen()] as $client) {
            $client->postJson($this->base().'/preview-targets', $payload)->assertForbidden();
            $client->postJson($this->base(), $payload + ['title' => 'Forbidden'])->assertForbidden();
        }

        $draft = $this->draft();
        $adminB = $this->adminUser(9);
        $this->actingAs($adminB, 'api')->withHeader('X-Active-Role', 'admin')->getJson("/api/arsip-digital/admin/institutional-distributions/$draft")->assertOk()->assertJsonPath('data.distribution.distribution_id', $draft);
        $this->actingAs($adminB, 'api')->withHeader('X-Active-Role', 'admin')->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk();
        $this->assertDatabaseHas('arsip_digital.audit_logs', ['action' => 'institutional_distribution.published', 'actor_user_id' => 9]);
    }

    public function test_specific_student_and_lecturer_preview_publish_exact_set_and_duplicates(): void
    {
        foreach ([['mahasiswa', ['22010001']], ['dosen', ['DSN001']]] as [$role, $identifiers]) {
            $payload = $this->targets($role, $identifiers);
            $preview = $this->actingAsAdmin()->postJson($this->base().'/preview-targets', $payload)->assertOk()->assertJsonPath('data.preview.total_valid', 1)->json('data.preview.valid_targets');
            $draft = $this->draft($payload);
            $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk()->assertJsonPath('data.distribution.recipients_count', 1);
            $actual = DB::table('arsip_digital.distribution_recipients')->where('distribution_id', $draft)->pluck('identifier')->all();
            $this->assertSame(collect($preview)->pluck('identifier')->all(), $actual);
        }

        $duplicate = $this->targets('mahasiswa', ['22010001', '22010001']);
        $draft = $this->draft($duplicate);
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk()->assertJsonPath('data.distribution.recipients_count', 1);
    }

    public function test_filter_segment_and_preview_over_one_hundred_have_exact_publish_parity(): void
    {
        $users = $this->students(101);
        $segment = DB::table('arsip_digital.segments')->insertGetId(['name' => 'Bulk', 'target_role' => 'mahasiswa', 'is_active' => 1, 'created_by_user_id' => 1]);
        DB::table('arsip_digital.segment_members')->insert(collect($users)->map(fn ($row) => ['segment_id' => $segment, 'target_user_id' => $row['id'], 'target_role' => 'mahasiswa', 'identifier' => $row['nim'], 'name_snapshot' => $row['name']])->all());
        foreach ([
            ['target_role' => 'mahasiswa', 'scope_type' => 'filter', 'target_filters' => ['has_account' => true]],
            ['target_role' => 'mahasiswa', 'scope_type' => 'segment', 'target_segment_ids' => [$segment]],
        ] as $payload) {
            $preview = $this->actingAsAdmin()->postJson($this->base().'/preview-targets', $payload)->assertOk()->json('data.preview');
            $this->assertGreaterThan(100, $preview['total_valid']);
            $this->assertCount(100, $preview['valid_targets']);
            $draft = $this->draft($payload);
            $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk()->assertJsonPath('data.distribution.recipients_count', $preview['total_valid']);
        }
    }

    public function test_invalid_empty_and_mixed_targets_never_partially_publish(): void
    {
        foreach ([[], ['UNKNOWN'], ['22010001', 'UNKNOWN']] as $identifiers) {
            $draft = $this->draft($this->targets('mahasiswa', $identifiers));
            $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertUnprocessable();
            $this->assertDatabaseHas('arsip_digital.distributions', ['distribution_id' => $draft, 'status' => 'draft', 'source_file_id' => null]);
            $this->assertSame(0, DB::table('arsip_digital.distribution_recipients')->where('distribution_id', $draft)->count());
        }
    }

    public function test_draft_exposes_authoritative_source_and_stale_publish_is_rejected(): void
    {
        $draft = $this->draft();
        $this->actingAsAdmin()->getJson("/api/arsip-digital/admin/institutional-distributions/$draft")
            ->assertOk()
            ->assertJsonPath('data.distribution.source_file_id', $this->fileId)
            ->assertJsonPath('data.distribution.source_file.file_id', $this->fileId)
            ->assertJsonPath('data.distribution.source_file.version_number', 1)
            ->assertJsonPath('data.distribution.source_file.display_filename', 'lembaga.pdf');

        $stale = $this->fileId;
        $this->actingAsAdmin()->post("/api/arsip-digital/admin/institutional-archives/$this->archiveId/versions", ['file' => $this->pdfUpload('v2.pdf'), 'reason' => 'V2'], ['Accept' => 'application/json'])->assertCreated();
        $current = DB::table('arsip_digital.institutional_archives')->where('institutional_archive_id', $this->archiveId)->value('current_file_id');
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $stale])->assertStatus(409);
        $this->assertDatabaseHas('arsip_digital.distributions', ['distribution_id' => $draft, 'status' => 'draft', 'source_file_id' => null]);
        $this->assertSame(0, DB::table('arsip_digital.distribution_recipients')->where('distribution_id', $draft)->count());
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $current])->assertOk();
        $this->assertDatabaseHas('arsip_digital.distributions', ['distribution_id' => $draft, 'status' => 'published', 'source_file_id' => $current]);
    }

    public function test_publish_pins_exact_version_reuses_source_and_is_idempotent(): void
    {
        $beforeObjects = Storage::disk('s3')->allFiles();
        $draft = $this->draft();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId + 999])->assertStatus(409);
        $this->actingAsAdmin()->post("/api/arsip-digital/admin/institutional-archives/$this->archiveId/versions", ['file' => $this->pdfUpload('v2.pdf'), 'reason' => 'V2'], ['Accept' => 'application/json'])->assertCreated();
        $this->assertDatabaseHas('arsip_digital.distributions', ['distribution_id' => $draft, 'source_file_id' => $this->fileId]);
        $this->assertDatabaseHas('arsip_digital.distribution_recipients', ['distribution_id' => $draft, 'file_id' => $this->fileId]);
        $this->assertSame(1, DB::table('arsip_digital.distribution_recipients')->where('distribution_id', $draft)->count());
        $this->assertSame(1, DB::table('arsip_digital.audit_logs')->where('action', 'institutional_distribution.published')->where('entity_id', (string) $draft)->count());
        $this->assertSame(1, DB::table('arsip_digital.notifications')->where('entity_type', 'distribution_recipient')->where('data', 'like', '%"distribution_id":'.$draft.'%')->count());
        $this->assertCount(count($beforeObjects) + 1, Storage::disk('s3')->allFiles());
    }

    public function test_expiry_null_future_offset_boundary_and_invalid_are_enforced(): void
    {
        Carbon::setTestNow('2026-08-03 10:00:00 UTC');
        try {
            $this->assertIsInt($this->draft($this->targets(), null));
            foreach (['2026-08-03T12:00:00+02:00', '2026-08-03T09:59:59+00:00', 'invalid'] as $expiry) {
                $this->actingAsAdmin()->postJson($this->base(), $this->targets() + ['title' => 'Expiry', 'expires_at' => $expiry])->assertUnprocessable();
            }
            $draft = $this->draft($this->targets(), '2026-08-04T10:00:00+00:00');
            $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk();
            $recipient = DB::table('arsip_digital.distribution_recipients')->where('distribution_id', $draft)->value('recipient_id');
            Carbon::setTestNow('2026-08-04 10:00:00 UTC');
            $this->actingAsMahasiswa()->get("/api/arsip-digital/distribution-recipients/$recipient/download")->assertGone();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_recipient_isolation_tracking_preview_missing_and_payload_privacy(): void
    {
        $draft = $this->draft();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk();
        $recipient = DB::table('arsip_digital.distribution_recipients')->where('distribution_id', $draft)->first();
        $this->actingAsDosen()->get("/api/arsip-digital/distribution-recipients/$recipient->recipient_id/download")->assertNotFound();
        $downloadAudits = DB::table('arsip_digital.audit_logs')->where('action', 'institutional_distribution.downloaded')->count();
        $this->actingAsMahasiswa()->get("/api/arsip-digital/distribution-recipients/$recipient->recipient_id/preview")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('cache-control', 'no-store, private')
            ->assertHeader('x-content-type-options', 'nosniff')
            ->assertHeader('content-disposition', 'inline; filename="lembaga.pdf"');
        $this->assertDatabaseHas('arsip_digital.distribution_recipients', ['recipient_id' => $recipient->recipient_id, 'download_count' => 0]);
        $this->assertSame($downloadAudits, DB::table('arsip_digital.audit_logs')->where('action', 'institutional_distribution.downloaded')->count());
        $this->actingAsMahasiswa()->get("/api/arsip-digital/distribution-recipients/$recipient->recipient_id/download")->assertOk();
        $this->actingAsMahasiswa()->get("/api/arsip-digital/distribution-files/$this->fileId/download")->assertOk();
        $this->assertDatabaseHas('arsip_digital.distribution_recipients', ['recipient_id' => $recipient->recipient_id, 'download_count' => 2, 'delivery_status' => 'downloaded']);
        $second = $this->draft();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$second/publish", ['source_file_id' => $this->fileId])->assertOk();
        $pageOne = $this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions?per_page=1&page=1')->assertOk()->assertJsonMissing(['storage_path', 'storage_disk', 'checksum_sha256', 'target_user_id', 'created_by_user_id'])->assertJsonPath('data.meta.current_page', 1)->assertJsonPath('data.meta.last_page', 2)->assertJsonPath('data.meta.total', 2);
        $pageTwo = $this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions?per_page=1&page=2')->assertOk()->assertJsonPath('data.meta.current_page', 2);
        $this->assertSame($second, $pageOne->json('data.distributions.0.distribution_id'));
        $this->assertSame($draft, $pageTwo->json('data.distributions.0.distribution_id'));
        $this->assertSame([$recipient->recipient_id], collect($pageTwo->json('data.distributions.0.recipients'))->pluck('recipient_id')->all());
        $this->actingAsDosen()->getJson('/api/arsip-digital/distributions')->assertOk()->assertJsonCount(0, 'data.distributions');
        $this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions?per_page=101')->assertUnprocessable();
        Storage::disk('s3')->delete(DB::table('arsip_digital.files')->where('file_id', $this->fileId)->value('storage_path'));
        $count = DB::table('arsip_digital.distribution_recipients')->where('recipient_id', $recipient->recipient_id)->value('download_count');
        $audits = DB::table('arsip_digital.audit_logs')->where('action', 'institutional_distribution.downloaded')->count();
        $this->actingAsMahasiswa()->get("/api/arsip-digital/distribution-recipients/$recipient->recipient_id/download")->assertNotFound();
        $this->assertSame($count, DB::table('arsip_digital.distribution_recipients')->where('recipient_id', $recipient->recipient_id)->value('download_count'));
        $this->assertSame($audits, DB::table('arsip_digital.audit_logs')->where('action', 'institutional_distribution.downloaded')->count());
        $this->actingAsMahasiswa()->get("/api/arsip-digital/files/$this->fileId/download")->assertForbidden();
    }

    public function test_withdraw_requires_reason_is_repeat_safe_and_preserves_source(): void
    {
        $draft = $this->draft();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertOk();
        $recipient = DB::table('arsip_digital.distribution_recipients')->where('distribution_id', $draft)->value('recipient_id');
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/withdraw", [])->assertUnprocessable();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/withdraw", ['reason' => ' Diganti '])->assertOk();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/withdraw", ['reason' => 'Lagi'])->assertOk();
        $this->actingAsMahasiswa()->get("/api/arsip-digital/distribution-recipients/$recipient/download")->assertGone();
        $this->assertDatabaseHas('arsip_digital.institutional_archives', ['institutional_archive_id' => $this->archiveId, 'status' => 'active', 'current_file_id' => $this->fileId]);
        $this->assertDatabaseHas('arsip_digital.files', ['file_id' => $this->fileId, 'status' => 'active', 'is_current' => 1]);
        $this->assertSame(1, DB::table('arsip_digital.audit_logs')->where('action', 'institutional_distribution.withdrawn')->where('entity_id', (string) $draft)->count());
    }

    public function test_user_list_hides_deleted_source_and_restore_only_reveals_live_published_distribution(): void
    {
        Carbon::setTestNow('2026-08-03 10:00:00 UTC');
        try {
            $distribution = $this->draft($this->targets(), '2026-08-04T10:00:00+00:00');
            $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$distribution/publish", ['source_file_id' => $this->fileId])->assertOk();
            $this->assertSame([$distribution], collect($this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions')->assertOk()->json('data.distributions'))->pluck('distribution_id')->all());

            $this->actingAsAdmin()->deleteJson("/api/arsip-digital/admin/institutional-archives/$this->archiveId", ['reason' => 'Hapus sementara'])->assertOk();
            $this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions')->assertOk()->assertJsonCount(0, 'data.distributions');
            $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-archives/$this->archiveId/restore")->assertOk();
            $this->assertSame([$distribution], collect($this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions')->assertOk()->json('data.distributions'))->pluck('distribution_id')->all());

            DB::table('arsip_digital.distributions')->where('distribution_id', $distribution)->update(['expires_at' => '2026-08-03 10:00:00']);
            $this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions')->assertOk()->assertJsonCount(0, 'data.distributions');
            DB::table('arsip_digital.distributions')->where('distribution_id', $distribution)->update(['expires_at' => null]);
            $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$distribution/withdraw", ['reason' => 'Tidak berlaku'])->assertOk();
            $this->actingAsMahasiswa()->getJson('/api/arsip-digital/distributions')->assertOk()->assertJsonCount(0, 'data.distributions');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_deleted_archive_blocks_create_publish_and_download_but_restore_allows_new_draft(): void
    {
        $draft = $this->draft();
        $this->actingAsAdmin()->deleteJson("/api/arsip-digital/admin/institutional-archives/$this->archiveId", ['reason' => 'Hapus'])->assertOk();
        $this->actingAsAdmin()->postJson($this->base(), $this->targets() + ['title' => 'No'])->assertNotFound();
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-distributions/$draft/publish", ['source_file_id' => $this->fileId])->assertStatus(409);
        $this->actingAsAdmin()->postJson("/api/arsip-digital/admin/institutional-archives/$this->archiveId/restore")->assertOk();
        $this->draft();
    }

    private function base(): string
    {
        return "/api/arsip-digital/admin/institutional-archives/$this->archiveId/distributions";
    }

    private function targets(string $role = 'mahasiswa', array $identifiers = ['22010001']): array
    {
        return ['target_role' => $role, 'scope_type' => 'specific', 'target_identifiers' => $identifiers];
    }

    private function draft(?array $targets = null, ?string $expires = null): int
    {
        $payload = ($targets ?? $this->targets()) + ['title' => 'Distribusi'];
        if ($expires !== null) {
            $payload['expires_at'] = $expires;
        }

        return $this->actingAsAdmin()->postJson($this->base(), $payload)->assertCreated()->json('data.distribution.distribution_id');
    }

    private function students(int $count): array
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $id = 100 + $i;
            $nim = sprintf('26%06d', $i);
            $name = "Student $i";
            DB::table('users')->insert(['id' => $id, 'kd_user' => "MHS-$nim", 'name' => $name, 'is_mhs' => 1]);
            DB::table('vmahasiswa')->insert(['mhs_id' => $id, 'nim' => $nim, 'nm_mhs' => $name, 'angkatan' => '2026', 'prodi' => 'TI', 'sts_mhs' => 'aktif']);
            $rows[] = compact('id', 'nim', 'name');
        }

        return $rows;
    }

    private function adminUser(int $id): UserView
    {
        DB::table('users')->insert(['id' => $id, 'kd_user' => 'ADM-B', 'name' => 'Admin B', 'is_admin' => 1]);
        $user = new UserView;
        $user->setRawAttributes(['id' => $id, 'kd_user' => 'ADM-B', 'name' => 'Admin B', 'is_admin' => true, 'is_mhs' => false, 'is_dosen' => false, 'is_staff' => false], true);

        return $user;
    }
}
