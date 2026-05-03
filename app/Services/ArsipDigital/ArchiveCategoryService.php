<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\Category;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ArchiveCategoryService
{
    public function __construct(private readonly ArchivePermissionService $permissions)
    {
    }

    public function queryFor(object $user, string $role, array $filters = []): Builder
    {
        $query = Category::query();

        if (! empty($filters['with_deleted']) && $role === 'admin') {
            $query->withTrashed();
        }

        if ($role === 'admin') {
            if (! empty($filters['category_type'])) {
                $query->where('category_type', $filters['category_type']);
            }

            if (! empty($filters['owner_role'])) {
                $query->where('owner_role', $filters['owner_role']);
            }

            if (! empty($filters['owner_user_id'])) {
                $query->where('owner_user_id', $filters['owner_user_id']);
            }

            return $query->orderBy('category_type')->orderBy('name');
        }

        return $query
            ->where(function (Builder $query) use ($user, $role): void {
                $query->where(function (Builder $query) use ($user, $role): void {
                    $query->where('category_type', 'personal')
                        ->where('owner_user_id', $user->id)
                        ->where('owner_role', $role);
                })->orWhere('category_type', 'official');
            })
            ->orderBy('category_type')
            ->orderBy('name');
    }

    public function create(array $payload, object $user, string $role): Category
    {
        $categoryType = $payload['category_type'] ?? ($role === 'admin' ? 'official' : 'personal');

        if ($role !== 'admin' && $categoryType !== 'personal') {
            throw new HttpException(403, 'Mahasiswa/dosen hanya dapat membuat kategori personal.');
        }

        if ($role === 'admin' && $categoryType !== 'official') {
            throw new HttpException(422, 'Admin Phase 2 hanya dapat membuat kategori official.');
        }

        $parentId = $payload['parent_category_id'] ?? null;
        if ($parentId !== null) {
            $this->assertParentAllowed((int) $parentId, $user, $role, $categoryType);
        }

        return Category::create([
            'owner_user_id' => $categoryType === 'personal' ? $user->id : null,
            'owner_role' => $categoryType === 'personal' ? $role : null,
            'category_type' => $categoryType,
            'name' => $payload['name'],
            'description' => $payload['description'] ?? null,
            'visibility' => $payload['visibility'] ?? ($categoryType === 'official' ? 'official' : 'admin_visible'),
            'parent_category_id' => $parentId,
            'created_by_user_id' => $user->id,
            'created_by_role' => $role,
            'is_system' => false,
        ]);
    }

    public function update(Category $category, array $payload, object $user, string $role): Category
    {
        if (! $this->permissions->canManageCategory($category, $user, $role)) {
            throw new HttpException(403, 'Tidak memiliki akses mengubah kategori.');
        }

        $parentId = $payload['parent_category_id'] ?? $category->parent_category_id;
        if ($parentId !== null && (int) $parentId !== (int) $category->category_id) {
            $this->assertParentAllowed((int) $parentId, $user, $role, $category->category_type);
        }

        if ($parentId !== null && (int) $parentId === (int) $category->category_id) {
            throw new HttpException(422, 'Kategori tidak boleh menjadi parent dirinya sendiri.');
        }

        $category->fill([
            'name' => $payload['name'] ?? $category->name,
            'description' => array_key_exists('description', $payload) ? $payload['description'] : $category->description,
            'visibility' => $payload['visibility'] ?? $category->visibility,
            'parent_category_id' => $parentId,
        ]);
        $category->save();

        return $category;
    }

    public function delete(Category $category, object $user, string $role): void
    {
        if (! $this->permissions->canManageCategory($category, $user, $role)) {
            throw new HttpException(403, 'Tidak memiliki akses menghapus kategori.');
        }

        $category->delete();
    }

    public function restore(int $categoryId): Category
    {
        $category = Category::withTrashed()->findOrFail($categoryId);
        $category->restore();

        return $category;
    }

    private function assertParentAllowed(int $parentId, object $user, string $role, string $categoryType): void
    {
        $parent = Category::findOrFail($parentId);

        if ($parent->category_type !== $categoryType) {
            throw new HttpException(422, 'Parent kategori harus memiliki tipe kategori yang sama.');
        }

        if (! $this->permissions->canManageCategory($parent, $user, $role)) {
            throw new HttpException(403, 'Tidak memiliki akses ke parent kategori.');
        }
    }
}
