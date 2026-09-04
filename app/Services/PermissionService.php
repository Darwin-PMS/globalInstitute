<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    /**
     * Cache key prefix for permission-related caches.
     */
    private const CACHE_PREFIX = 'rbac:';

    /**
     * Get all effective permission slugs for a user (cached).
     */
    public function getUserPermissions(User $user): array
    {
        if ($user->hasRole('super-admin')) {
            return $this->getAllPermissionSlugs();
        }

        $cacheKey = self::CACHE_PREFIX . "user:{$user->id}:permissions";

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            return $user->getEffectivePermissions();
        });
    }

    /**
     * Check if a user has a specific permission.
     */
    public function userHasPermission(User $user, string $permissionSlug): bool
    {
        $permissions = $this->getUserPermissions($user);
        return in_array($permissionSlug, $permissions);
    }

    /**
     * Check if a user has ALL of the given permissions.
     */
    public function userHasAllPermissions(User $user, array $permissionSlugs): bool
    {
        $permissions = $this->getUserPermissions($user);
        return count(array_intersect($permissionSlugs, $permissions)) === count($permissionSlugs);
    }

    /**
     * Check if a user has ANY of the given permissions.
     */
    public function userHasAnyPermission(User $user, array $permissionSlugs): bool
    {
        $permissions = $this->getUserPermissions($user);
        return count(array_intersect($permissionSlugs, $permissions)) > 0;
    }

    /**
     * Get all permission slugs in the system (cached).
     */
    public function getAllPermissionSlugs(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'all_slugs', 3600, function () {
            return Permission::where('is_active', true)
                ->pluck('slug')
                ->toArray();
        });
    }

    /**
     * Get permissions grouped by module (cached).
     */
    public function getGroupedPermissions(): array
    {
        return Cache::remember(self::CACHE_PREFIX . 'grouped', 3600, function () {
            return Permission::where('is_active', true)
                ->orderBy('module')
                ->orderBy('action')
                ->get()
                ->groupBy('module')
                ->map(fn ($perms) => $perms->pluck('slug', 'action'))
                ->toArray();
        });
    }

    /**
     * Invalidate cached permissions for a user.
     */
    public function invalidateUserPermissions(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . "user:{$userId}:permissions");
    }

    /**
     * Invalidate all permission caches (call when roles/permissions change).
     */
    public function invalidateAll(): void
    {
        // Invalidate grouped permissions
        Cache::forget(self::CACHE_PREFIX . 'all_slugs');
        Cache::forget(self::CACHE_PREFIX . 'grouped');

        // Invalidate all user caches
        $keys = Cache::get(self::CACHE_PREFIX . 'user_ids', []);
        foreach ($keys as $userId) {
            Cache::forget(self::CACHE_PREFIX . "user:{$userId}:permissions");
        }
    }

    /**
     * Get the menu items the user can see from a collection of AdminMenu items.
     */
    public function filterMenus(User $user, $menus): array
    {
        if ($user->hasRole('super-admin')) {
            return $menus->toArray();
        }

        return $menus->filter(function ($menu) use ($user) {
            // No permission required — always visible
            if (empty($menu->permission_key)) {
                return true;
            }

            // User has the required permission
            return $user->hasPermission($menu->permission_key);
        })->toArray();
    }
}
