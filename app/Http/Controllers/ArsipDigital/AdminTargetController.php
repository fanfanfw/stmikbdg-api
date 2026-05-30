<?php

namespace App\Http\Controllers\ArsipDigital;

use App\Exceptions\ErrorHandler;
use App\Http\Controllers\Controller;
use App\Models\Users\DosenView;
use App\Models\Users\MahasiswaView;
use App\Models\Users\User;
use App\Services\ArsipDigital\RoleResolverService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminTargetController extends Controller
{
    public function index(Request $request, RoleResolverService $roleResolver)
    {
        try {
            $roleResolver->resolve($request, ['admin']);

            $filters = $request->validate([
                'role' => ['required', 'in:mahasiswa,dosen'],
                'search' => ['sometimes', 'nullable', 'string', 'max:255'],
                'angkatan' => ['sometimes', 'nullable', 'integer'],
                'status' => ['sometimes', 'nullable', 'string', 'max:20'],
                'has_account' => ['sometimes', 'boolean'],
                'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            ]);

            $targets = $filters['role'] === 'mahasiswa'
                ? $this->mahasiswaTargets($filters)
                : $this->dosenTargets($filters);

            return $this->successfulResponseJSON([
                'targets' => $targets->items(),
                'meta' => [
                    'current_page' => $targets->currentPage(),
                    'last_page' => $targets->lastPage(),
                    'per_page' => $targets->perPage(),
                    'total' => $targets->total(),
                ],
            ]);
        } catch (\Exception $e) {
            return ErrorHandler::handle($e);
        }
    }

    private function mahasiswaTargets(array $filters)
    {
        $query = MahasiswaView::query()
            ->select(['nim', 'nm_mhs', 'masuk_tahun', 'sts_mhs'])
            ->whereNotNull('nim');

        if (! empty($filters['search'])) {
            $search = '%' . strtoupper($filters['search']) . '%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('nim', 'like', $search)
                    ->orWhere('nm_mhs', 'like', $search);
            });
        }

        if (! empty($filters['angkatan'])) {
            $query->where('masuk_tahun', $filters['angkatan']);
        }

        if (! empty($filters['status'])) {
            $query->where('sts_mhs', strtoupper($filters['status']));
        }

        $accounts = $this->accountMap('MHS-');
        $accountIdentifiers = $accounts->keys()
            ->map(fn (string $kdUser): string => substr($kdUser, 4))
            ->all();

        if (array_key_exists('has_account', $filters)) {
            $expected = filter_var($filters['has_account'], FILTER_VALIDATE_BOOL);
            $expected ? $query->whereIn('nim', $accountIdentifiers) : $query->whereNotIn('nim', $accountIdentifiers);
        }

        return $query->orderByDesc('masuk_tahun')
            ->orderBy('nim')
            ->paginate($filters['per_page'] ?? 25)
            ->through(fn ($item): array => [
                'role' => 'mahasiswa',
                'identifier' => trim((string) $item->nim),
                'name' => trim((string) $item->nm_mhs),
                'angkatan' => $item->masuk_tahun,
                'status' => trim((string) $item->sts_mhs),
                'has_account' => $accounts->has('MHS-' . trim((string) $item->nim)),
            ]);
    }

    private function dosenTargets(array $filters)
    {
        $query = DosenView::query()
            ->select(['kd_dosen', 'nm_dosen', 'sts_dosen'])
            ->whereNotNull('kd_dosen');

        if (! empty($filters['search'])) {
            $search = '%' . strtoupper($filters['search']) . '%';
            $query->where(function (Builder $query) use ($search): void {
                $query->where('kd_dosen', 'like', $search)
                    ->orWhere('nm_dosen', 'like', $search);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('sts_dosen', strtoupper($filters['status']));
        }

        $accounts = $this->accountMap('DSN-');
        $accountIdentifiers = $accounts->keys()
            ->map(fn (string $kdUser): string => substr($kdUser, 4))
            ->all();

        if (array_key_exists('has_account', $filters)) {
            $expected = filter_var($filters['has_account'], FILTER_VALIDATE_BOOL);
            $expected ? $query->whereIn('kd_dosen', $accountIdentifiers) : $query->whereNotIn('kd_dosen', $accountIdentifiers);
        }

        return $query->orderBy('kd_dosen')
            ->paginate($filters['per_page'] ?? 25)
            ->through(fn ($item): array => [
                'role' => 'dosen',
                'identifier' => trim((string) $item->kd_dosen),
                'name' => trim((string) $item->nm_dosen),
                'angkatan' => null,
                'status' => trim((string) $item->sts_dosen),
                'has_account' => $accounts->has('DSN-' . trim((string) $item->kd_dosen)),
            ]);
    }

    private function accountMap(string $prefix)
    {
        return User::where('kd_user', 'like', $prefix . '%')
            ->pluck('id', 'kd_user');
    }
}
