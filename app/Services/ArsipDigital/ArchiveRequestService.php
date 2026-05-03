<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveRequest;
use App\Models\ArsipDigital\RequestAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ArchiveRequestService
{
    public function __construct(
        private readonly RequestTargetPreviewService $targetPreview,
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function adminQuery(array $filters = []): Builder
    {
        $query = ArchiveRequest::query();

        if (! empty($filters['with_deleted'])) {
            $query->withTrashed();
        }

        foreach (['target_role', 'scope_type', 'status'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }

        return $query->orderByDesc('created_at')->orderByDesc('request_id');
    }

    public function userQuery(object $user, string $role): Builder
    {
        return ArchiveRequest::where('status', 'published')
            ->whereHas('assignments', function (Builder $query) use ($user, $role): void {
                $query->where('target_user_id', $user->id)
                    ->where('target_role', $role);
            })
            ->with(['assignments' => function ($query) use ($user, $role): void {
                $query->where('target_user_id', $user->id)
                    ->where('target_role', $role)
                    ->with('requestFiles.file');
            }])
            ->orderByDesc('published_at')
            ->orderByDesc('request_id');
    }

    public function create(array $payload, object $user): ArchiveRequest
    {
        return ArchiveRequest::create([
            'title' => $payload['title'],
            'description' => $payload['description'] ?? null,
            'target_role' => $payload['target_role'],
            'scope_type' => $payload['scope_type'],
            'target_filters' => $payload['target_filters'] ?? null,
            'target_identifiers' => $payload['target_identifiers'] ?? null,
            'target_segment_ids' => $payload['target_segment_ids'] ?? null,
            'max_files' => $payload['max_files'] ?? 1,
            'max_file_size_mb' => $payload['max_file_size_mb'] ?? null,
            'allowed_extensions' => isset($payload['allowed_extensions'])
                ? array_values(array_unique(array_map('strtolower', $payload['allowed_extensions'])))
                : null,
            'requires_verification' => $payload['requires_verification'] ?? true,
            'allow_file_reuse' => $payload['allow_file_reuse'] ?? true,
            'allow_inactive_upload' => $payload['allow_inactive_upload'] ?? false,
            'deadline_at' => $payload['deadline_at'] ?? null,
            'close_after_deadline' => $payload['close_after_deadline'] ?? false,
            'status' => 'draft',
            'created_by_user_id' => $user->id,
        ]);
    }

    public function update(ArchiveRequest $request, array $payload): ArchiveRequest
    {
        if ($request->status !== 'draft') {
            throw new HttpException(422, 'Request hanya dapat diubah saat status draft.');
        }

        $request->fill([
            'title' => $payload['title'] ?? $request->title,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $request->description,
            'target_role' => $payload['target_role'] ?? $request->target_role,
            'scope_type' => $payload['scope_type'] ?? $request->scope_type,
            'target_filters' => array_key_exists('target_filters', $payload) ? $payload['target_filters'] : $request->target_filters,
            'target_identifiers' => array_key_exists('target_identifiers', $payload) ? $payload['target_identifiers'] : $request->target_identifiers,
            'target_segment_ids' => array_key_exists('target_segment_ids', $payload) ? $payload['target_segment_ids'] : $request->target_segment_ids,
            'max_files' => $payload['max_files'] ?? $request->max_files,
            'max_file_size_mb' => array_key_exists('max_file_size_mb', $payload) ? $payload['max_file_size_mb'] : $request->max_file_size_mb,
            'allowed_extensions' => isset($payload['allowed_extensions'])
                ? array_values(array_unique(array_map('strtolower', $payload['allowed_extensions'])))
                : $request->allowed_extensions,
            'requires_verification' => $payload['requires_verification'] ?? $request->requires_verification,
            'allow_file_reuse' => $payload['allow_file_reuse'] ?? $request->allow_file_reuse,
            'allow_inactive_upload' => $payload['allow_inactive_upload'] ?? $request->allow_inactive_upload,
            'deadline_at' => array_key_exists('deadline_at', $payload) ? $payload['deadline_at'] : $request->deadline_at,
            'close_after_deadline' => $payload['close_after_deadline'] ?? $request->close_after_deadline,
        ]);
        $request->save();

        return $request;
    }

    public function delete(ArchiveRequest $request): void
    {
        if ($request->status !== 'draft') {
            throw new HttpException(422, 'Request hanya dapat dihapus saat status draft.');
        }

        $request->delete();
    }

    public function previewForPayload(array $payload): array
    {
        return $this->targetPreview->preview($payload);
    }

    public function previewForRequest(ArchiveRequest $request): array
    {
        return $this->targetPreview->preview([
            'target_role' => $request->target_role,
            'scope_type' => $request->scope_type,
            'target_filters' => $request->target_filters ?? [],
            'target_identifiers' => $request->target_identifiers ?? [],
            'target_segment_ids' => $request->target_segment_ids ?? [],
        ]);
    }

    public function publish(ArchiveRequest $request, object $actor, string $actorRole, $httpRequest = null): ArchiveRequest
    {
        if ($request->status !== 'draft') {
            throw new HttpException(422, 'Hanya request draft yang dapat dipublish.');
        }

        $preview = $this->previewForRequest($request);

        if ($preview['total_invalid'] > 0) {
            throw new HttpException(422, 'Request tidak dapat dipublish karena masih memiliki target invalid.');
        }

        if ($preview['total_valid'] < 1) {
            throw new HttpException(422, 'Request tidak dapat dipublish tanpa target valid.');
        }

        return DB::connection(config('myconfig.database.first_connection'))->transaction(function () use ($request, $preview, $actor, $actorRole, $httpRequest): ArchiveRequest {
            $request->fill([
                'status' => 'published',
                'published_at' => now(),
            ]);
            $request->save();

            foreach ($preview['valid_targets'] as $target) {
                $assignment = RequestAssignment::create([
                    'request_id' => $request->request_id,
                    'target_user_id' => $target['target_user_id'],
                    'target_role' => $target['target_role'],
                    'identifier' => $target['identifier'],
                    'name_snapshot' => $target['name_snapshot'],
                    'angkatan_snapshot' => $target['angkatan_snapshot'],
                    'prodi_snapshot' => $target['prodi_snapshot'],
                    'status_snapshot' => $target['status_snapshot'],
                    'scholarship_snapshot' => $target['scholarship_snapshot'],
                    'metadata' => $target['metadata'],
                    'status' => 'not_submitted',
                ]);

                $this->auditLog->record(
                    'request_assignment.created',
                    'request_assignment',
                    $assignment->assignment_id,
                    'Assignment request arsip digital dibuat saat publish.',
                    ['request_id' => $request->request_id, 'identifier' => $assignment->identifier],
                    $httpRequest,
                    $actor->id,
                    $actorRole
                );
            }

            $this->auditLog->record(
                'request.published',
                'request',
                $request->request_id,
                'Request arsip digital dipublish.',
                ['total_assignments' => $preview['total_valid']],
                $httpRequest,
                $actor->id,
                $actorRole
            );

            return $request->fresh(['assignments']);
        });
    }

    public function progress(ArchiveRequest $request): array
    {
        $counts = RequestAssignment::where('request_id', $request->request_id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $submitted = RequestAssignment::where('request_id', $request->request_id)
            ->whereIn('status', ['waiting_verification', 'approved', 'rejected'])
            ->count();

        $total = RequestAssignment::where('request_id', $request->request_id)->count();

        return [
            'request_id' => $request->request_id,
            'total_assignments' => $total,
            'submitted' => $submitted,
            'pending' => (int) ($counts['not_submitted'] ?? 0),
            'waiting_verification' => (int) ($counts['waiting_verification'] ?? 0),
            'approved' => (int) ($counts['approved'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'closed' => (int) ($counts['closed'] ?? 0),
        ];
    }
}
