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
use LxAuth\Contracts\PermissionInterface;

class Permission extends Model implements PermissionInterface
{
    protected $table = 'permissions';

    protected $fillable = [
        'slug',
        'name',
        'description',
        'is_system',
        'is_wildcard'
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_wildcard' => 'boolean'
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isWildcard(): bool
    {
        return (bool)$this->is_wildcard;
    }

    public function matches(string $permission): bool
    {
        if ($this->slug === '*' || $this->slug === $permission) {
            return true;
        }

        if ($this->is_wildcard) {
            // Escapar caracteres especiales primero, luego reemplazar \* por .*
            $pattern = preg_quote($this->slug, '/');
            $pattern = str_replace('\*', '.*', $pattern);
            return (bool)preg_match('/^' . $pattern . '$/', $permission);
        }

        return false;
    }

    public function isSystemPermission(): bool
    {
        return (bool)$this->is_system;
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'permission_role',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'permission_user',
            'permission_id',
            'user_id'
        )->withPivot('grant')->withTimestamps();
    }

}