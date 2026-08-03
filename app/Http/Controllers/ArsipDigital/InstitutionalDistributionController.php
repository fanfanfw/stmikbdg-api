<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\ArsipDigital\Distribution;
use App\Models\ArsipDigital\InstitutionalArchive;
use App\Services\ArsipDigital\InstitutionalDistributionService;
use App\Services\ArsipDigital\RoleResolverService;
use Illuminate\Http\Request;

class InstitutionalDistributionController extends Controller
{
    public function preview(Request $request, int $id, RoleResolverService $roles, InstitutionalDistributionService $service)
    {
        try {
            $roles->resolve($request, ['admin']);
            InstitutionalArchive::findOrFail($id);

            return $this->successfulResponseJSON(['preview' => $service->preview($this->targets($request))]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function store(Request $request, int $id, RoleResolverService $roles, InstitutionalDistributionService $service)
    {
        try {
            $role = $roles->resolve($request, ['admin']);
            $payload = array_merge($this->targets($request), $request->validate(['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'expires_at' => ['nullable', 'date_format:Y-m-d\\TH:i:sP', 'after:now']]));
            $item = $service->create(InstitutionalArchive::findOrFail($id), $payload, auth()->user(), $role, $request);

            return $this->successfulResponseJSON(['distribution' => $service->distributionDto($item)], 'Draft distribusi dibuat.', 201);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function index(Request $request, int $id, RoleResolverService $roles, InstitutionalDistributionService $service)
    {
        try {
            $roles->resolve($request, ['admin']);
            InstitutionalArchive::withTrashed()->findOrFail($id);
            $items = Distribution::where('institutional_archive_id', $id)->withCount('recipients')->orderByDesc('distribution_id')->paginate(min((int) $request->input('per_page', 20), 100));

            return $this->successfulResponseJSON(['distributions' => collect($items->items())->map(fn ($item) => $service->distributionDto($item))->all(), 'meta' => ['current_page' => $items->currentPage(), 'last_page' => $items->lastPage(), 'total' => $items->total()]]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function show(Request $request, int $distributionId, RoleResolverService $roles, InstitutionalDistributionService $service)
    {
        try {
            $roles->resolve($request, ['admin']);
            $item = $this->find($distributionId)->load(['institutionalArchive.unit', 'sourceFile'])->loadCount('recipients');

            return $this->successfulResponseJSON(['distribution' => $service->distributionDto($item)]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function recipients(Request $request, int $distributionId, RoleResolverService $roles, InstitutionalDistributionService $service)
    {
        try {
            $roles->resolve($request, ['admin']);
            $item = $this->find($distributionId);
            $rows = $item->recipients()->orderBy('identifier')->paginate(min((int) $request->input('per_page', 50), 100));

            return $this->successfulResponseJSON(['recipients' => collect($rows->items())->map(fn ($recipient) => $service->recipientDto($recipient))->all(), 'meta' => ['current_page' => $rows->currentPage(), 'last_page' => $rows->lastPage(), 'total' => $rows->total()]]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function publish(Request $request, int $distributionId, RoleResolverService $roles, InstitutionalDistributionService $service)
    {
        try {
            $role = $roles->resolve($request, ['admin']);
            $item = $service->publish($this->find($distributionId), auth()->user(), $role, $request);

            return $this->successfulResponseJSON(['distribution' => $service->distributionDto($item)], 'Distribusi dipublish.');
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function withdraw(Request $request, int $distributionId, RoleResolverService $roles, InstitutionalDistributionService $service)
    {
        try {
            $role = $roles->resolve($request, ['admin']);
            $reason = $request->validate(['reason' => ['required', 'string', 'max:2000']])['reason'];
            $item = $service->withdraw($this->find($distributionId), $reason, auth()->user(), $role, $request);

            return $this->successfulResponseJSON(['distribution' => $service->distributionDto($item)], 'Distribusi ditarik.');
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function targets(Request $request): array
    {
        return $request->validate(['target_role' => ['required', 'in:mahasiswa,dosen'], 'scope_type' => ['required', 'in:filter,specific,segment'], 'target_filters' => ['nullable', 'array'], 'target_identifiers' => ['nullable'], 'target_segment_ids' => ['nullable', 'array'], 'target_segment_ids.*' => ['integer']]);
    }

    private function find(int $id): Distribution
    {
        return Distribution::whereNotNull('institutional_archive_id')->findOrFail($id);
    }
}
