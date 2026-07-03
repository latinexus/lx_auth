<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:39
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Drivers\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use LxAuth\Contracts\UserInterface;
use LxAuth\Models\User;
use LxAuth\Models\Role;
use LxAuth\Models\Permission;

class EloquentDriver implements DatabaseDriverInterface
{
    private $connection;
    private array $config;
    private static ?Capsule $capsule = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        // No inicializar conexión automáticamente
        // El usuario debe configurarla manualmente o usar setCapsule()
    }

    public function getConnection()
    {
        if ($this->connection === null && self::$capsule !== null) {
            $this->connection = self::$capsule->getDatabaseManager();
        }

        return $this->connection;
    }

    public function setConnection($connection): void
    {
        $this->connection = $connection;
    }

    /**
     * Establece una instancia de Capsule configurada externamente
     */
    public static function setCapsule(Capsule $capsule): void
    {
        self::$capsule = $capsule;
    }

    /**
     * Obtiene la instancia de Capsule
     */
    public static function getCapsule(): ?Capsule
    {
        return self::$capsule;
    }

    public function beginTransaction(): void
    {
        if ($this->getConnection()) {
            $this->getConnection()->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->getConnection()) {
            $this->getConnection()->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->getConnection()) {
            $this->getConnection()->rollBack();
        }
    }

    // ========== USER METHODS ==========

    public function findUserById($id): ?UserInterface
    {
        return User::find($id);
    }

    public function findUserByCredentials(array $credentials): ?UserInterface
    {
        if (empty($credentials['email'])) {
            return null;
        }

        return User::where('email', $credentials['email'])->first();
    }

    public function userExists(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function createUser(array $data): UserInterface
    {
        return User::create($data);
    }

    public function updateUser(UserInterface $user, array $data): bool
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User debe ser instancia de LxAuth\\Models\\User');
        }

        return $user->update($data);
    }

    public function deleteUser(UserInterface $user): bool
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User debe ser instancia de LxAuth\\Models\\User');
        }

        $user->roles()->detach();
        $user->userPermissions()->detach();

        return (bool)$user->delete();
    }

    public function assignRole(UserInterface $user, string $role): bool
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User debe ser instancia de LxAuth\\Models\\User');
        }

        $roleModel = $this->findRoleBySlug($role);
        if (!$roleModel) {
            return false;
        }

        $user->roles()->syncWithoutDetaching([$roleModel->getId()]);
        return true;
    }

    public function removeRole(UserInterface $user, string $role): bool
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User debe ser instancia de LxAuth\\Models\\User');
        }

        $roleModel = $this->findRoleBySlug($role);
        if (!$roleModel) {
            return false;
        }

        $user->roles()->detach($roleModel->getId());
        return true;
    }

    public function getRoles(UserInterface $user): array
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User debe ser instancia de LxAuth\\Models\\User');
        }

        return $user->roles->all();
    }

    // ========== ROLE METHODS ==========

    public function createRole(array $data)
    {
        return Role::create($data);
    }

    public function findRoleBySlug(string $slug)
    {
        return Role::where('slug', $slug)->first();
    }

    // ========== PERMISSION METHODS ==========

    public function createPermission(array $data)
    {
        return Permission::create($data);
    }

    public function findPermissionBySlug(string $slug)
    {
        return Permission::where('slug', $slug)->first();
    }

    public function getPermissionsForUser(UserInterface $user): array
    {
        $permissions = [];

        // Permisos directos del usuario (desde JSON)
        foreach ($user->getDirectPermissions() as $permission => $grant) {
            $permissions[$permission] = $grant;
        }

        // Permisos desde tabla pivote
        if (method_exists($user, 'userPermissions')) {
            foreach ($user->userPermissions as $permission) {
                if ($permission instanceof Permission) {
                    $permissions[$permission->getSlug()] = (bool)($permission->pivot->grant ?? true);
                }
            }
        }

        // Permisos de roles
        foreach ($user->getRoles() as $role) {
            if ($role instanceof Role) {
                foreach ($role->getPermissions() as $permission) {
                    if (!isset($permissions[$permission->getSlug()])) {
                        $permissions[$permission->getSlug()] = true;
                    }
                }
            }
        }

        return $permissions;
    }

    public function getAllPermissions(): array
    {
        return Permission::all()->all();
    }

    // ========== HELPER METHODS ==========

    public function withTransaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
}