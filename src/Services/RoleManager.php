<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:30
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services;

use LxAuth\Contracts\UserInterface;
use LxAuth\Drivers\Database\DatabaseDriverInterface;
use LxAuth\Exceptions\RoleException;

class RoleManager
{
    private DatabaseDriverInterface $driver;
    private array $config;
    private array $cache = [];

    public function __construct(DatabaseDriverInterface $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    public function assignRole(UserInterface $user, string $role): void
    {
        $roleModel = $this->driver->findRoleBySlug($role, $user->getTenantId());

        if (!$roleModel) {
            throw new RoleException("El rol '{$role}' no existe");
        }

        if (!$this->driver->assignRole($user, $role)) {
            throw new RoleException("No se pudo asignar el rol '{$role}'");
        }

        $this->clearUserCache($user);
    }

    public function removeRole(UserInterface $user, string $role): void
    {
        $roleModel = $this->driver->findRoleBySlug($role, $user->getTenantId());

        if (!$roleModel) {
            throw new RoleException("El rol '{$role}' no existe");
        }

        if ($roleModel->isSystemRole()) {
            throw new RoleException("No se puede remover un rol del sistema");
        }

        if (!$this->driver->removeRole($user, $role)) {
            throw new RoleException("No se pudo remover el rol '{$role}'");
        }

        $this->clearUserCache($user);
    }

    public function userHasRole(UserInterface $user, string $role): bool
    {
        $cacheKey = $this->getUserCacheKey($user, "role_{$role}");

        if ($this->config['cache_enabled'] && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $hasRole = $user->hasRole($role);

        if ($this->config['cache_enabled']) {
            $this->cache[$cacheKey] = $hasRole;
        }

        return $hasRole;
    }

    private function getUserCacheKey(UserInterface $user, string $suffix): string
    {
        return "user_{$user->getId()}_{$user->getTenantId()}_{$suffix}";
    }

    private function clearUserCache(UserInterface $user): void
    {
        $prefix = "user_{$user->getId()}_{$user->getTenantId()}_";

        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->cache[$key]);
            }
        }
    }
}