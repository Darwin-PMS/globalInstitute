<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminMenu;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['permissions', 'users', 'menus'])->latest()->get();

        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        $menus = AdminMenu::sidebar()->active()->ordered()->get();
        $groupedPermissions = Permission::active()->grouped()->get()->groupBy('module');

        return view('admin.roles.create', compact('menus', 'groupedPermissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:1000',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,slug',
            'menus' => 'nullable|array',
            'menus.*' => 'exists:admin_menus,id',
        ]);

        DB::beginTransaction();
        try {
            $role = Role::create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'description' => $validated['description'] ?? null,
            ]);

            // Sync permissions
            if (!empty($validated['permissions'])) {
                $permIds = Permission::whereIn('slug', $validated['permissions'])->pluck('id');
                $role->permissions()->sync($permIds);
            }

            // Sync menus
            if (!empty($validated['menus'])) {
                $role->menus()->sync($validated['menus']);
            }

            AuditLog::log([
                'action' => 'role_created',
                'module' => 'roles',
                'subject_type' => Role::class,
                'subject_id' => $role->id,
                'new_values' => $role->toArray(),
            ]);

            app(PermissionService::class)->invalidateAll();

            return redirect()->route('admin.roles.index')
                ->with('success', "Role \"{$role->name}\" created successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    public function edit(Role $role)
    {
        $role->load(['permissions', 'menus']);
        $menus = AdminMenu::sidebar()->active()->ordered()->get();
        $groupedPermissions = Permission::active()->grouped()->get()->groupBy('module');
        $assignedPermSlugs = $role->permissions->pluck('slug')->toArray();
        $assignedMenuIds = $role->menus->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'menus', 'groupedPermissions', 'assignedPermSlugs', 'assignedMenuIds'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,slug',
            'menus' => 'nullable|array',
            'menus.*' => 'exists:admin_menus,id',
        ]);

        DB::beginTransaction();
        try {
            $oldValues = $role->toArray();

            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => isset($validated['is_active']),
            ]);

            // Sync permissions
            $permIds = Permission::whereIn('slug', $validated['permissions'] ?? [])->pluck('id');
            $role->permissions()->sync($permIds);

            // Sync menus
            $role->menus()->sync($validated['menus'] ?? []);

            AuditLog::log([
                'action' => 'role_updated',
                'module' => 'roles',
                'subject_type' => Role::class,
                'subject_id' => $role->id,
                'old_values' => $oldValues,
                'new_values' => $role->fresh()->toArray(),
            ]);

            app(PermissionService::class)->invalidateAll();

            return redirect()->route('admin.roles.index')
                ->with('success', "Role \"{$role->name}\" updated successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be deleted. Deactivate them instead.');
        }

        if ($role->users()->count() > 0) {
            return back()->with('error', 'Cannot delete a role that has assigned users. Remove users first.');
        }

        $name = $role->name;
        $role->delete();

        AuditLog::log([
            'action' => 'role_deleted',
            'module' => 'roles',
            'subject_type' => Role::class,
            'notes' => "Deleted role: {$name}",
        ]);

        app(PermissionService::class)->invalidateAll();

        return redirect()->route('admin.roles.index')
            ->with('success', "Role \"{$name}\" deleted successfully.");
    }

    public function duplicate(Role $role)
    {
        $newRole = Role::create([
            'name' => $role->name . ' (Copy)',
            'slug' => $role->slug . '-copy',
            'description' => $role->description,
        ]);

        // Copy permissions
        $permIds = $role->permissions->pluck('id');
        $newRole->permissions()->sync($permIds);

        // Copy menus
        $menuIds = $role->menus->pluck('id');
        $newRole->menus()->sync($menuIds);

        AuditLog::log([
            'action' => 'role_duplicated',
            'module' => 'roles',
            'subject_type' => Role::class,
            'subject_id' => $newRole->id,
            'notes' => "Duplicated from role: {$role->name}",
        ]);

        return redirect()->route('admin.roles.edit', $newRole)
            ->with('success', "Role duplicated as \"{$newRole->name}\".");
    }
}
