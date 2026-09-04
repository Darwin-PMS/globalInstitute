<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class UserPermissionController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('roles');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('slug', $request->role));
        }

        $users = $query->latest()->paginate(20);
        $roles = Role::active()->get();

        return view('admin.users.index', compact('users', 'roles'));
    }

    public function permissions(User $user)
    {
        $user->load(['roles.permissions', 'directPermissions']);
        $groupedPermissions = Permission::active()->grouped()->get()->groupBy('module');
        $rolePermSlugs = $user->roles->flatMap->permissions->pluck('slug')->unique()->toArray();
        $directPerms = $user->directPermissions->keyBy('slug');

        return view('admin.users.permissions', compact('user', 'groupedPermissions', 'rolePermSlugs', 'directPerms'));
    }

    public function updatePermissions(Request $request, User $user)
    {
        $validated = $request->validate([
            'overrides' => 'nullable|array',
            'overrides.*.slug' => 'required|string|exists:permissions,slug',
            'overrides.*.type' => 'required|in:allow,deny',
        ]);

        // Clear existing overrides
        $user->directPermissions()->detach();

        // Add new overrides
        if (!empty($validated['overrides'])) {
            foreach ($validated['overrides'] as $override) {
                $perm = Permission::where('slug', $override['slug'])->first();
                if ($perm) {
                    $user->directPermissions()->attach($perm->id, [
                        'type' => $override['type'],
                    ]);
                }
            }
        }

        AuditLog::log([
            'action' => 'user_permissions_updated',
            'module' => 'users',
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'new_values' => ['overrides' => $validated['overrides'] ?? []],
            'notes' => "Updated permission overrides for {$user->name}",
        ]);

        app(PermissionService::class)->invalidateUserPermissions($user->id);

        return redirect()->route('admin.users.permissions', $user)
            ->with('success', "Permissions updated for \"{$user->name}\".");
    }
}
