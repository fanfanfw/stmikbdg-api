<?php

namespace App\Services\ArsipDigital;

use App\Models\ArsipDigital\ArchiveFile;
use App\Models\ArsipDigital\Category;

class ArchivePermissionService
{
    public function canManageCategory(Category $category, object $user, string $role): bool
    {
        if ($role === 'admin') {
            return $category->category_type === 'official'
                || $category->created_by_user_id === $user->id
                || $category->owner_user_id === $user->id;
        }

        return $category->category_type === 'personal'
            && $category->owner_user_id === $user->id
            && $category->owner_role === $role;
    }

    public function canViewCategory(Category $category, object $user, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }

        if ($category->category_type === 'official') {
            return true;
        }

        return $category->category_type === 'personal'
            && $category->owner_user_id === $user->id
            && $category->owner_role === $role;
    }

    public function canUploadToCategory(Category $category, object $user, string $role): bool
    {
        return $category->category_type === 'personal'
            && $category->owner_user_id === $user->id
            && $category->owner_role === $role;
    }

    public function canViewFile(ArchiveFile $file, object $user, string $role): bool
    {
        if ($role === 'admin') {
            return true;
        }

        return $file->owner_user_id === $user->id && $file->owner_role === $role;
    }

    public function canDeleteFile(ArchiveFile $file, object $user, string $role): bool
    {
        return $this->canViewFile($file, $user, $role);
    }

    public function canDownloadFile(ArchiveFile $file, object $user, string $role): bool
    {
        return $this->canViewFile($file, $user, $role);
    }
}
