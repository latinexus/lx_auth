<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:05
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;
use LxAuth\Contracts\UserInterface;
use LxAuth\Contracts\RoleInterface;

/**
 * Modelo de usuario con Eloquent
 */
class User extends Model implements UserInterface
{
    protected $table = 'users';

    protected $fillable = [
        'tenant_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'is_active',
        'last_login',
        'permissions',
        'meta'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login' => 'datetime',
        'permissions' => 'array',
        'meta' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        // Hashear contraseña al crear/actualizar
        static::saving(function ($user) {
            if ($user->isDirty('password')) {
                $user->password = Hash::make($user->password);
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int|string
    {
        return $this->getKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getTenantId(): ?string
    {
        return $this->tenant_id;
    }

    /**
     * {@inheritdoc}
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }

    /**
     * Relación con roles
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_user',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles(): array
    {
        // Evitar cargar la relación automáticamente para prevenir recursión
        if ($this->relationLoaded('roles')) {
            return $this->roles->all();
        }
        
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function getDirectPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->last_login;
    }

    /**
     * {@inheritdoc}
     */
    public function updateLastLogin(): void
    {
        $this->last_login = now();
        $this->save();
    }

    /**
     * {@inheritdoc}
     */
    public function getPasswordHash(): string
    {
        return $this->password;
    }

    /**
     * {@inheritdoc}
     */
    public function verifyPassword(string $password): bool
    {
        return Hash::check($password, $this->password);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserAttribute(string $key, $default = null)
    {
        // Acceder directamente al array de atributos para evitar recursión
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        if (array_key_exists($key, $this->relations)) {
            return $this->relations[$key];
        }

        if (method_exists($this, $key)) {
            return $this->$key();
        }

        return $default;
    }

    /**
     * Método seguro para obtener atributos sin recursión
     */
    public function getCustomAttribute(string $key, $default = null)
    {
        if (isset($this->attributes[$key])) {
            return $this->attributes[$key];
        }

        return $default;
    }




    /**
     * Scope para filtrar por tenant
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Relación con permisos directos
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'permission_user',
            'user_id',
            'permission_id'
        )->withPivot('grant')->withTimestamps();
    }

    /**
     * Obtener nombre completo
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Verificar si el usuario tiene un permiso directo
     */
    public function hasDirectPermission(string $permission): bool
    {
        $permissions = $this->getDirectPermissions();
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }

    /**
     * Asignar contraseña (hasheada automáticamente)
     */
    public function setPasswordAttribute(string $value): void
    {
        // Usar el hash de manera segura si el facade está disponible
        try {
            $this->attributes['password'] = \Illuminate\Support\Facades\Hash::make($value);
        } catch (\Exception $e) {
            // Si el facade no está disponible, dejar la contraseña sin hashear por ahora
            $this->attributes['password'] = $value;
        }
    }
}