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
use LxAuth\Models\Tenant;

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

    public function findUserById($id, string $tenantId): ?UserInterface
    {
        return User::forTenant($tenantId)->find($id);
    }

    public function findUserByCredentials(array $credentials, string $tenantId): ?UserInterface
    {
        if (empty($credentials['email'])) {
            return null;
        }

        return User::forTenant($tenantId)
            ->where('email', $credentials['email'])
            ->first();
    }

    public function userExists(string $email, string $tenantId): bool
    {
        return User::forTenant($tenantId)
            ->where('email', $email)
            ->exists();
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
        $user->permissions()->detach();

        return (bool)$user->delete();
    }

    public function assignRole(UserInterface $user, string $role): bool
    {
        if (!$user instanceof User) {
            throw new \InvalidArgumentException('User debe ser instancia de LxAuth\\Models\\User');
        }

        $roleModel = $this->findRoleBySlug($role, $user->getTenantId());
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

        $roleModel = $this->findRoleBySlug($role, $user->getTenantId());
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

    public function findRoleBySlug(string $slug, string $tenantId)
    {
        return Role::forTenant($tenantId)
            ->where('slug', $slug)
            ->first();
    }

    // ========== PERMISSION METHODS ==========

    public function createPermission(array $data)
    {
        return Permission::create($data);
    }

    public function findPermissionBySlug(string $slug, string $tenantId)
    {
        return Permission::forTenant($tenantId)
            ->where('slug', $slug)
            ->first();
    }

    public function getPermissionsForUser(UserInterface $user): array
    {
        $permissions = [];

        // Permisos directos del usuario (desde JSON)
        foreach ($user->getDirectPermissions() as $permission => $grant) {
            $permissions[$permission] = $grant;
        }

        // Permisos desde tabla pivote
        if (method_exists($user, 'permissions')) {
            foreach ($user->permissions as $permission) {
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

    public function getAllPermissions(string $tenantId): array
    {
        return Permission::forTenant($tenantId)->get()->all();
    }

    // ========== TENANT METHODS ==========

    public function findTenantById(string $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public function findTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }

    public function createTenant(array $data): Tenant
    {
        return Tenant::create($data);
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