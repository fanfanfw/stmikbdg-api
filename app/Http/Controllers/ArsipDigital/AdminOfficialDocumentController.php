<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\ArsipDigital\OfficialDocument;
use App\Services\ArsipDigital\OfficialDocumentIssuanceService;
use App\Services\ArsipDigital\RoleResolverService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminOfficialDocumentController extends Controller
{
    public function index(Request $request, RoleResolverService $roleResolver)
    {
        try {
            $roleResolver->resolve($request, ['admin']);
            $filters = $request->validate([
                'document_type' => ['sometimes', 'in:khs,transcript'],
                'status' => ['sometimes', 'in:issued,revoked,replaced'],
                'search' => ['sometimes', 'string', 'max:255'],
                'page' => ['sometimes', 'integer', 'min:1'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $documents = OfficialDocument::query()
                ->with('file')
                ->when($filters['document_type'] ?? null, fn (Builder $query, string $type) => $query->where('document_type', $type))
                ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
                ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                    $query->where(function (Builder $query) use ($search): void {
                        $query->where('document_number', 'ilike', '%'.$search.'%')
                            ->orWhere('subject_identifier', 'ilike', '%'.$search.'%')
                            ->orWhere('subject_name_snapshot', 'ilike', '%'.$search.'%');
                    });
                })
                ->orderByDesc('issued_at')
                ->orderByDesc('official_document_id')
                ->paginate($filters['per_page'] ?? 25);

            return $this->successfulResponseJSON([
                'documents' => $documents->items(),
                'meta' => [
                    'current_page' => $documents->currentPage(),
                    'last_page' => $documents->lastPage(),
                    'per_page' => $documents->perPage(),
                    'total' => $documents->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function store(
        Request $request,
        RoleResolverService $roleResolver,
        OfficialDocumentIssuanceService $issuanceService
    ) {
        try {
            $role = $roleResolver->resolve($request, ['admin']);
            $payload = $request->validate([
                'document_type' => ['required', 'in:khs,transcript'],
                'document_number' => ['required', 'string', 'max:100'],
                'mhs_id' => ['required', 'integer', 'min:1'],
                'semester' => ['required_if:document_type,khs', 'nullable', 'integer', 'min:1', 'max:20'],
            ]);

            $document = $issuanceService->issue($payload, auth()->user(), $role, $request);

            return $this->successfulResponseJSON(
                ['document' => $document->toArray()],
                'Dokumen akademik resmi berhasil diterbitkan.',
                201
            );
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function show(Request $request, int $official_document_id, RoleResolverService $roleResolver)
    {
        try {
            $roleResolver->resolve($request, ['admin']);
            $document = OfficialDocument::with('file')->findOrFail($official_document_id);

            return $this->successfulResponseJSON(['document' => $document->toArray()]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
