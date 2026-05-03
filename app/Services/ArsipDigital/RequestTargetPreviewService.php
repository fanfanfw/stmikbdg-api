<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\Segment;
use App\Models\ArsipDigital\SegmentMember;
use App\Models\ArsipDigital\StudentScholarship;
use App\Models\Users\MahasiswaView;
use App\Models\Users\User;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RequestTargetPreviewService
{
    public function __construct(private readonly TargetResolverService $targetResolver)
    {
    }

    public function preview(array $payload): array
    {
        $targetRole = $payload['target_role'];
        $scopeType = $payload['scope_type'];
        $filters = $payload['target_filters'] ?? [];
        $identifiers = $payload['target_identifiers'] ?? [];
        $segmentIds = $payload['target_segment_ids'] ?? [];

        $candidates = match ($scopeType) {
            'all' => $this->allIdentifiers($targetRole),
            'filter' => $this->filterIdentifiers($targetRole, $filters),
            'specific' => collect($this->normalizeIdentifiers($identifiers)),
            'segment' => $this->segmentIdentifiers($targetRole, $segmentIds),
            default => throw new HttpException(422, 'Scope target tidak valid.'),
        };

        $valid = [];
        $invalid = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $identifier = is_array($candidate) ? ($candidate['identifier'] ?? null) : $candidate;
            $identifier = trim((string) $identifier);

            if ($identifier === '' || isset($seen[$identifier])) {
                continue;
            }

            $seen[$identifier] = true;
            $resolved = $this->targetResolver->resolve($targetRole, $identifier);

            if (! $resolved['valid']) {
                $invalid[] = [
                    'target_role' => $targetRole,
                    'identifier' => $identifier,
                    'reason' => $resolved['error'],
                ];

                continue;
            }

            $valid[] = [
                'target_user_id' => $resolved['target_user_id'],
                'target_role' => $targetRole,
                'identifier' => $resolved['identifier'],
                'name_snapshot' => $resolved['name_snapshot'],
                'angkatan_snapshot' => $resolved['angkatan_snapshot'],
                'prodi_snapshot' => $resolved['prodi_snapshot'],
                'status_snapshot' => $resolved['status_snapshot'],
                'scholarship_snapshot' => $this->scholarshipSnapshot($targetRole, $resolved['identifier']),
                'metadata' => [
                    'scope_type' => $scopeType,
                ],
            ];
        }

        return [
            'target_role' => $targetRole,
            'scope_type' => $scopeType,
            'total_targets' => count($valid) + count($invalid),
            'total_valid' => count($valid),
            'total_invalid' => count($invalid),
            'valid_targets' => $valid,
            'invalid_targets' => $invalid,
        ];
    }

    private function allIdentifiers(string $targetRole): Collection
    {
        $prefix = match ($targetRole) {
            'mahasiswa' => 'MHS-',
            'dosen' => 'DSN-',
            default => throw new HttpException(422, 'Target role harus mahasiswa atau dosen.'),
        };

        return User::where('kd_user', 'like', $prefix . '%')
            ->pluck('kd_user')
            ->map(fn (string $kdUser): string => substr($kdUser, strlen($prefix)));
    }

    private function filterIdentifiers(string $targetRole, array $filters): Collection
    {
        if ($targetRole === 'dosen') {
            return $this->allIdentifiers('dosen');
        }

        $hasScholarshipFilter = ! empty($filters['scholarship_type_ids']) || ! empty($filters['scholarship_status']);

        if ($hasScholarshipFilter) {
            $query = StudentScholarship::query();

            if (! empty($filters['scholarship_type_ids'])) {
                $query->whereIn('scholarship_type_id', (array) $filters['scholarship_type_ids']);
            }

            if (! empty($filters['scholarship_status'])) {
                $query->whereIn('status', (array) $filters['scholarship_status']);
            }

            if (! empty($filters['angkatan'])) {
                $query->whereIn('angkatan_snapshot', (array) $filters['angkatan']);
            }

            return $query->pluck('nim')->unique()->values();
        }

        $query = MahasiswaView::query();

        if (! empty($filters['angkatan'])) {
            $query->whereIn('masuk_tahun', (array) $filters['angkatan']);
        }

        if (! empty($filters['student_status'])) {
            $query->whereIn('sts_mhs', (array) $filters['student_status']);
        }

        return $query->pluck('nim')->unique()->values();
    }

    private function segmentIdentifiers(string $targetRole, array $segmentIds): Collection
    {
        if (empty($segmentIds)) {
            return collect();
        }

        $segments = Segment::whereIn('segment_id', $segmentIds)
            ->where('target_role', $targetRole)
            ->where('is_active', true)
            ->pluck('segment_id');

        return SegmentMember::whereIn('segment_id', $segments)
            ->where('target_role', $targetRole)
            ->pluck('identifier')
            ->unique()
            ->values();
    }

    private function normalizeIdentifiers(mixed $identifiers): array
    {
        if (is_string($identifiers)) {
            $identifiers = preg_split('/[\s,]+/', $identifiers, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (! is_array($identifiers)) {
            return [];
        }

        return array_values(array_filter(array_map(function (mixed $item): string {
            if (is_array($item)) {
                return trim((string) ($item['identifier'] ?? ''));
            }

            return trim((string) $item);
        }, $identifiers)));
    }

    private function scholarshipSnapshot(string $targetRole, string $identifier): ?array
    {
        if ($targetRole !== 'mahasiswa') {
            return null;
        }

        $items = StudentScholarship::with('scholarshipType')
            ->where('nim', $identifier)
            ->whereNull('deleted_at')
            ->get()
            ->map(fn (StudentScholarship $scholarship): array => [
                'scholarship_type_id' => $scholarship->scholarship_type_id,
                'scholarship_name' => $scholarship->scholarshipType?->name,
                'status' => $scholarship->status,
                'period_label' => $scholarship->period_label,
            ])
            ->values()
            ->toArray();

        return empty($items) ? null : $items;
    }
}
