<?php

namespace App\Services;

use App\Models\Library;
use App\Models\LibraryUser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SubscriptionPermissionService
{
    public function finalPermissions(Library|LibraryUser|null $user): Collection
    {
        $library = $this->libraryFor($user);

        if (! $library || empty($library->library_type)) {
            return collect();
        }

        $slugSelect = Schema::hasColumn('permissions', 'slug')
            ? 'permissions.slug'
            : DB::raw('NULL as slug');

        $query = DB::table('subscription_permission')
            ->join('permissions', 'permissions.id', '=', 'subscription_permission.permission_id')
            ->where('subscription_permission.subscription_id', $library->library_type)
            ->select('permissions.id', 'permissions.name', $slugSelect);

        if ($user instanceof LibraryUser) {
            $query->join('library_user_permissions', function ($join) use ($user) {
                $join->on('library_user_permissions.permission_id', '=', 'permissions.id')
                    ->where('library_user_permissions.library_user_id', $user->id);
            });
        }

        return $query
            ->distinct()
            ->orderBy('permissions.name')
            ->get()
            ->map(function ($permission) {
                return [
                    'id' => (int) $permission->id,
                    'name' => $permission->name,
                    'slug' => $this->permissionSlug($permission->slug ?? null, $permission->name),
                ];
            });
    }

    public function permissionStatusMap(Library|LibraryUser|null $user): array
    {
        $slugSelect = Schema::hasColumn('permissions', 'slug')
            ? 'permissions.slug'
            : DB::raw('NULL as slug');

        $allowedSlugs = $this->finalPermissions($user)
            ->pluck('slug')
            ->flip();

        return DB::table('permissions')
            ->where('guard_name', 'library')
            ->select('name', $slugSelect)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($permission) use ($allowedSlugs) {
                $slug = $this->permissionSlug($permission->slug ?? null, $permission->name);

                return [$slug => $allowedSlugs->has($slug)];
            })
            ->all();
    }

    public function has(Library|LibraryUser|null $user, string|array|null $permission): bool
    {
        $permissions = collect((array) $permission)
            ->filter()
            ->map(fn ($item) => trim((string) $item))
            ->filter();

        if ($permissions->isEmpty()) {
            return true;
        }

        return $this->finalPermissions($user)->contains(function (array $allowed) use ($permissions) {
            return $permissions->contains($allowed['name'])
                || $permissions->contains($allowed['slug']);
        });
    }

    private function libraryFor(Library|LibraryUser|null $user): ?Library
    {
        if ($user instanceof Library) {
            return $user;
        }

        if ($user instanceof LibraryUser) {
            return $user->parentLibrary ?: Library::find($user->library_id);
        }

        return null;
    }

    private function permissionSlug(?string $slug, string $name): string
    {
        $slug = trim((string) $slug);

        return $slug !== '' ? $slug : Str::slug($name);
    }
}
