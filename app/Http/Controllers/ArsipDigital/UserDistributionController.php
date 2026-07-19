<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Services\ArsipDigital\ArsipDigitalStorageService;
use App\Services\ArsipDigital\DistributionService;
use App\Services\ArsipDigital\RoleResolverService;
use Illuminate\Http\Request;

class UserDistributionController extends Controller
{
    public function index(Request $request, RoleResolverService $roleResolver, DistributionService $distributionService)
    {
        try {
            $role = $roleResolver->resolve($request, ['mahasiswa', 'dosen']);

            return $this->successfulResponseJSON([
                'distributions' => $distributionService->userQuery(auth()->user(), $role)->get()->toArray(),
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    public function download(
        Request $request,
        int $file_id,
        RoleResolverService $roleResolver,
        DistributionService $distributionService,
        ArsipDigitalStorageService $storageService
    ) {
        try {
            $role = $roleResolver->resolve($request, ['mahasiswa', 'dosen']);
            $recipient = $distributionService->findDownloadableRecipientByFile($file_id, auth()->user(), $role);
            $recipient = $distributionService->markDownloaded($recipient, auth()->user(), $role, $request);
            $file = $recipient->file;

            return $storageService->downloadPrivate($file->storage_disk, $file->storage_path, $file->display_filename);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }
}
