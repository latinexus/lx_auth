<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:28
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LxAuth\Contracts\RoleInterface;

class Role extends Model implements RoleInterface
{
    protected $table = 'roles';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'parent_id',
        'level',
        'is_system'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'level' => 'integer'
    ];

    public function getId(): int|string
    {
        return $this->getKey();
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_role',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    public function getPermissions(): array
    {
        return $this->permissions->all();
    }

    public function hasPermission(string $permission): bool
    {
        foreach ($this->permissions as $perm) {
            if ($perm->matches($permission)) {
                return true;
            }
        }

        if ($this->parent) {
            return $this->parent->hasPermission($permission);
        }

        return false;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    public function getParent(): ?RoleInterface
    {
        return $this->parent;
    }

    public function children(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_id');
    }

    public function getChildren(): array
    {
        return $this->children->all();
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function isSystemRole(): bool
    {
        return (bool)$this->is_system;
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'role_user',
            'role_id',
            'user_id'
        )->withTimestamps();
    }

}