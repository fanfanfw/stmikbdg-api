<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\ArchiveRequest;
use App\Models\ArsipDigital\RequestAssignment;
use App\Services\ArsipDigital\ArchiveRequestService;
use App\Services\ArsipDigital\RequestSubmissionService;
use App\Services\ArsipDigital\RoleResolverService;
use Illuminate\Http\Request;

class UserRequestController extends Controller
{
    public function index(Request $request, RoleResolverService $roleResolver, ArchiveRequestService $requestService)
    {
        try {
            $role = $roleResolver->resolve($request, ['mahasiswa', 'dosen']);

            return $this->successfulResponseJSON([
                'requests' => $requestService->userQuery(auth()->user(), $role)->get()->toArray(),
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function show(Request $request, int $request_id, RoleResolverService $roleResolver)
    {
        try {
            $role = $roleResolver->resolve($request, ['mahasiswa', 'dosen']);
            $archiveRequest = ArchiveRequest::where('request_id', $request_id)
                ->whereHas('assignments', function ($query) use ($role): void {
                    $query->where('target_user_id', auth()->user()->id)
                        ->where('target_role', $role);
                })
                ->with(['assignments' => function ($query) use ($role): void {
                    $query->where('target_user_id', auth()->user()->id)
                        ->where('target_role', $role)
                        ->with('requestFiles.file');
                }])
                ->firstOrFail();

            return $this->successfulResponseJSON(['request' => $archiveRequest->toArray()]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function upload(Request $request, int $assignment_id, RoleResolverService $roleResolver, RequestSubmissionService $submissionService)
    {
        try {
            $role = $roleResolver->resolve($request, ['mahasiswa', 'dosen']);
            $payload = $request->validate([
                'file' => ['required', 'file'],
                'display_filename' => ['nullable', 'string', 'max:255'],
                'note' => ['nullable', 'string'],
            ]);
            $assignment = RequestAssignment::with('request')->findOrFail($assignment_id);
            $requestFile = $submissionService->upload($assignment, $request->file('file'), $payload, auth()->user(), $role, $request);

            return $this->successfulResponseJSON(['request_file' => $requestFile->toArray()], 'File request berhasil diupload.', 201);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function reuse(Request $request, int $assignment_id, RoleResolverService $roleResolver, RequestSubmissionService $submissionService)
    {
        try {
            $role = $roleResolver->resolve($request, ['mahasiswa', 'dosen']);
            $payload = $request->validate([
                'file_id' => ['required', 'integer'],
            ]);
            $assignment = RequestAssignment::with('request')->findOrFail($assignment_id);
            $file = ArchiveFile::findOrFail($payload['file_id']);
            $requestFile = $submissionService->reuse($assignment, $file, auth()->user(), $role, $request);

            return $this->successfulResponseJSON(['request_file' => $requestFile->toArray()], 'File lama berhasil dipakai untuk request.', 201);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
