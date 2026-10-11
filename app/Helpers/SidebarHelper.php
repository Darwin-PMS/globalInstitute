<?php

namespace App\Helpers;

use App\Models\AdminMenu;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SidebarHelper
{
    private const CACHE_KEY = 'admin:sidebar:';

    /**
     * Get the full sidebar tree for a user, filtered by permissions.
     *
     * @return array Nested menu structure
     */
    public static function forUser(User $user): array
    {
        // Super Admin sees everything
        if ($user->hasRole('super-admin')) {
            return self::getFullTree();
        }

        $cacheKey = self::CACHE_KEY . $user->id;

        return Cache::remember($cacheKey, 3600, function () use ($user) {
            $menuIds = $user->roles
                ->flatMap->menus
                ->pluck('id')
                ->unique();

            // Include parent menus even when is_visible=false,
            // because they group child items in the sidebar.
            $menus = AdminMenu::whereIn('id', $menuIds)
                ->where('is_active', true)
                ->sidebar()
                ->ordered()
                ->get();

            return self::buildTree($menus);
        });
    }

    /**
     * Get the full sidebar tree (no permission filtering).
     *
     * Parent menus (section headers) are included even when is_visible=false
     * because they group child items. The Blade template renders them as
     * section headers and only shows children underneath.
     */
    public static function getFullTree(): array
    {
        $cacheKey = self::CACHE_KEY . 'all';

        return Cache::remember($cacheKey, 3600, function () {
            $menus = AdminMenu::where('is_active', true)
                ->sidebar()
                ->ordered()
                ->get();

            return self::buildTree($menus);
        });
    }

    /**
     * Build a nested tree from a flat collection of menus.
     */
    private static function buildTree($menus): array
    {
        $grouped = $menus->groupBy(fn ($m) => $m->parent_id ?? 'root');

        $buildChildren = function ($parentId) use (&$grouped, &$buildChildren) {
            return $grouped->get($parentId, collect())
                ->map(function ($menu) use (&$buildChildren) {
                    $item = [
                        'id' => $menu->id,
                        'name' => $menu->name,
                        'slug' => $menu->slug,
                        'icon' => $menu->icon,
                        'route_name' => $menu->route_name,
                        'url' => $menu->url,
                        'permission_key' => $menu->permission_key,
                        'is_spa' => $menu->is_spa,
                        'target' => $menu->target,
                    ];

                    $children = $buildChildren($menu->id);
                    if (count($children) > 0) {
                        $item['children'] = $children;
                    }

                    return $item;
                })
                ->values()
                ->toArray();
        };

        return $buildChildren('root');
    }

    /**
     * Invalidate the sidebar cache for a user.
     */
    public static function invalidateForUser(int $userId): void
    {
        Cache::forget(self::CACHE_KEY . $userId);
    }

    /**
     * Invalidate all sidebar caches.
     */
    public static function invalidateAll(): void
    {
        // This is a simple approach; in production you'd track cache keys
        Cache::forget(self::CACHE_KEY . 'all');
    }
}
