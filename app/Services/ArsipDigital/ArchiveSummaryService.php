<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\DistributionRecipient;
use App\Models\ArsipDigital\RequestAssignment;

class ArchiveSummaryService
{
    public function summaryFor(object $user, string $role): array
    {
        if ($role === 'admin') {
            return [
                'role' => $role,
                'total_files' => ArchiveFile::where('status', 'active')->count(),
                'pending_requests' => RequestAssignment::whereIn('status', ['not_submitted', 'waiting_verification'])->count(),
                'rejected_requests' => RequestAssignment::where('status', 'rejected')->count(),
                'distribution_files' => DistributionRecipient::whereIn('delivery_status', ['available', 'downloaded'])->count(),
            ];
        }

        return [
            'role' => $role,
            'total_files' => ArchiveFile::where('owner_user_id', $user->id)
                ->where('owner_role', $role)
                ->where('status', 'active')
                ->count(),
            'pending_requests' => RequestAssignment::where('target_user_id', $user->id)
                ->where('target_role', $role)
                ->whereIn('status', ['not_submitted', 'waiting_verification'])
                ->count(),
            'rejected_requests' => RequestAssignment::where('target_user_id', $user->id)
                ->where('target_role', $role)
                ->where('status', 'rejected')
                ->count(),
            'distribution_files' => DistributionRecipient::where('target_user_id', $user->id)
                ->where('target_role', $role)
                ->whereIn('delivery_status', ['available', 'downloaded'])
                ->count(),
        ];
    }
}
