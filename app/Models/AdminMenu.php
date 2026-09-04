<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\HasManyThrough;

class AdminMenu extends Model
{
    use HasFactory;

    protected $table = 'admin_menus';

    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'route_name',
        'url',
        'icon',
        'permission_key',
        'menu_type',
        'sort_order',
        'is_active',
        'is_visible',
        'target',
        'is_spa',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_spa' => 'boolean',
    ];

    // ── Relationships ──────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'role_menus', 'role_id')
            ->withPivot('role_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menus', 'menu_id', 'role_id')
            ->withTimestamps();
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_visible', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeSidebar($query)
    {
        return $query->where('menu_type', 'sidebar')
            ->orWhere('menu_type', 'both');
    }

    // ── Helpers ────────────────────────────────────────────

    /**
     * Get all child menu items recursively.
     */
    public function allChildren(): \Illuminate\Support\Collection
    {
        return static::where('parent_id', $this->id)
            ->active()
            ->ordered()
            ->get()
            ->each(function ($child) {
                $child->setRelation('allChildren', $child->allChildren());
            });
    }

    /**
     * Check if this menu requires a specific permission.
     */
    public function requiresPermission(): bool
    {
        return !empty($this->permission_key);
    }
}
