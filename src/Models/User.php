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
use LxAuth\Contracts\UserInterface;
use LxAuth\Contracts\RoleInterface;

/**
 * Modelo de usuario con Eloquent
 */
class User extends Model implements UserInterface
{
    protected $table = 'users';

    protected $fillable = [
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
        // Nota: El hasheo de contraseña se maneja via setPasswordAttribute mutator
        // que es invocado por Eloquent automáticamente al asignar $model->password
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
        return $this->roles->all();
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
        $this->last_login = new \DateTime();
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
        return password_verify($password, $this->password);
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
     * Relación con permisos directos (tabla pivote permission_user)
     * Nota: nombre distincto de la columna JSON 'permissions' para evitar conflicto en Eloquent
     */
    public function userPermissions(): BelongsToMany
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
     * Hashea la contraseña automáticamente al asignarla.
     * Usa password_hash() nativo de PHP, no requiere illuminate/events.
     */
    public function setPasswordAttribute(string $value): void
    {
        // Solo hashear si aún no está hasheada (evita doble hash)
        if (!password_get_info($value)['algo']) {
            $this->attributes['password'] = password_hash($value, PASSWORD_BCRYPT);
        } else {
            $this->attributes['password'] = $value;
        }
    }


}