<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'phone',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'role' => 'string',
        ];
    }

    public function students(): HasMany
    {
        return $this->hasMany(\App\Models\Student::class);
    }

    /**
     * The student profile for this login.
     *
     * A login owns at most one student record in practice; `students()` stays
     * for the historical has-many callers.
     */
    public function student(): HasOne
    {
        return $this->hasOne(\App\Models\Student::class);
    }

    /**
     * The trainer profile for this login, if the account teaches.
     */
    public function trainer(): HasOne
    {
        return $this->hasOne(\App\Models\Trainer::class);
    }

    /**
     * The single source of truth for "is this account an administrator".
     *
     * `is_admin` is the authoritative flag: it is set explicitly by the admin
     * seeder and by admin management. The legacy `role` column is not usable on
     * its own because it was created with a default of 'admin', so every new
     * account would otherwise inherit administrator rights.
     */
    public function isAdministrator(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Effective role for this account, resolved server-side.
     * Presentation code should use this rather than inferring from relations.
     */
    public function learningRole(): string
    {
        if ($this->isAdministrator()) {
            return 'admin';
        }

        return $this->trainer ? 'teacher' : 'student';
    }

    // ── RBAC Relationships ───────────────────────────────────

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withTimestamps();
    }

    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
            ->withPivot('type', 'note')
            ->withTimestamps();
    }

    // ── RBAC Helpers ─────────────────────────────────────────

    /**
     * Get all permission slugs from the user's roles (merged, no duplicates).
     */
    public function getRolePermissions(): \Illuminate\Support\Collection
    {
        return $this->roles()
            ->active()
            ->with('permissions')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('slug')
            ->unique();
    }

    /**
     * Get the user's effective permissions after resolving:
     * 1. Super Admin bypass
     * 2. Role permissions
     * 3. User direct DENY overrides
     * 4. User direct ALLOW overrides
     *
     * Returns array of permission slugs.
     */
    public function getEffectivePermissions(): array
    {
        // Super Admin bypass — has everything
        if ($this->hasRole('super-admin')) {
            return Permission::where('is_active', true)
                ->pluck('slug')
                ->toArray();
        }

        // Start with role-based permissions
        $allowed = $this->getRolePermissions()->flip();

        // Apply direct user DENY overrides
        $this->directPermissions()
            ->wherePivot('type', 'deny')
            ->each(function ($perm) use (&$allowed) {
                $allowed->forget($perm->slug);
            });

        // Apply direct user ALLOW overrides
        $this->directPermissions()
            ->wherePivot('type', 'allow')
            ->each(function ($perm) use (&$allowed) {
                $allowed->put($perm->slug, true);
            });

        return $allowed->keys()->toArray();
    }

    /**
     * Check if the user has a specific permission.
     * Super Admin always returns true.
     */
    public function hasPermission(string $permissionSlug): bool
    {
        // Super Admin bypass
        if ($this->hasRole('super-admin')) {
            return true;
        }

        // Check user-level DENY override first
        $directDeny = $this->directPermissions()
            ->where('slug', $permissionSlug)
            ->wherePivot('type', 'deny')
            ->exists();

        if ($directDeny) {
            return false;
        }

        // Check user-level ALLOW override
        $directAllow = $this->directPermissions()
            ->where('slug', $permissionSlug)
            ->wherePivot('type', 'allow')
            ->exists();

        if ($directAllow) {
            return true;
        }

        // Check role permissions
        return $this->roles()
            ->active()
            ->whereHas('permissions', function ($q) use ($permissionSlug) {
                $q->where('slug', $permissionSlug)->where('is_active', true);
            })
            ->exists();
    }

    /**
     * Check if the user has ALL of the given permissions.
     */
    public function hasAllPermissions(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check if the user has ANY of the given permissions.
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        foreach ($permissionSlugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $roleSlug): bool
    {
        return $this->roles()
            ->where('slug', $roleSlug)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Check if the user has any of the given roles.
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        return $this->roles()
            ->whereIn('slug', $roleSlugs)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Assign a role to the user (idempotent).
     */
    public function assignRole(string $roleSlug): self
    {
        $role = Role::where('slug', $roleSlug)->firstOrFail();
        if (!$this->roles()->where('role_id', $role->id)->exists()) {
            $this->roles()->attach($role);
        }
        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $roleSlug): self
    {
        $role = Role::where('slug', $roleSlug)->first();
        if ($role) {
            $this->roles()->detach($role);
        }
        return $this;
    }

    /**
     * Laravel's Gate integration — makes $user->can('students.view') work.
     */
    public function can($ability, $arguments = []): bool
    {
        // Super Admin bypass
        if ($this->hasRole('super-admin')) {
            return true;
        }

        // If it looks like a permission slug, check via RBAC
        if (str_contains($ability, '.')) {
            return $this->hasPermission($ability);
        }

        return parent::can($ability, $arguments);
    }

    /**
     * Inverse of can().
     */
    public function cant($ability, $arguments = []): bool
    {
        return !$this->can($ability, $arguments);
    }
}
