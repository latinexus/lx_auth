<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:31
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services;

use LxAuth\Contracts\UserInterface;
use LxAuth\Drivers\Database\DatabaseDriverInterface;
use LxAuth\Exceptions\PermissionException;

class PermissionManager
{
    private DatabaseDriverInterface $driver;
    private array $config;
    private array $cache = [];

    public function __construct(DatabaseDriverInterface $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;
    }

    public function userCan(UserInterface $user, string $permission, ?array $context = null): bool
    {
        $cacheKey = $this->getUserCacheKey($user, "perm_{$permission}");

        if ($this->config['cache_enabled'] && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $can = $this->checkPermission($user, $permission, $context);

        if ($this->config['cache_enabled']) {
            $this->cache[$cacheKey] = $can;
        }

        return $can;
    }

    public function givePermissionTo(UserInterface $user, string $permission, bool $grant = true): void
    {
        if ($this->isSystemPermission($permission)) {
            throw new PermissionException("No se puede modificar un permiso del sistema");
        }

        $permissionModel = $this->driver->findPermissionBySlug($permission);

        if (!$permissionModel) {
            $permissionModel = $this->driver->createPermission([
                'slug' => $permission,
                'name' => $this->slugToName($permission),
                'is_wildcard' => $this->isWildcard($permission),
            ]);
        }

        $user->userPermissions()->syncWithoutDetaching([
            $permissionModel->getId() => ['grant' => $grant]
        ]);

        $this->clearUserCache($user);
    }

    private function checkPermission(UserInterface $user, string $permission, ?array $context = null): bool
    {
        $directPermissions = $user->getDirectPermissions();
        if (isset($directPermissions[$permission])) {
            return (bool)$directPermissions[$permission];
        }

        $dbPermissions = $this->driver->getPermissionsForUser($user);

        if (isset($dbPermissions[$permission])) {
            return (bool)$dbPermissions[$permission];
        }

        if ($this->config['wildcard_enabled']) {
            foreach ($dbPermissions as $perm => $grant) {
                if ($this->matchesWildcard($perm, $permission)) {
                    return (bool)$grant;
                }
            }
        }

        if (isset($dbPermissions['*']) && $dbPermissions['*']) {
            return true;
        }

        return false;
    }

    private function matchesWildcard(string $pattern, string $permission): bool
    {
        if ($pattern === $permission) {
            return true;
        }

        // Escapar caracteres especiales de regex
        $regexPattern = preg_quote($pattern, '/');
        // Reemplazar \* por .* (wildcard → regex)
        $regexPattern = str_replace('\*', '.*', $regexPattern);

        return (bool)preg_match("/^{$regexPattern}$/", $permission);
    }

    private function isWildcard(string $permission): bool
    {
        return str_contains($permission, '*') || str_contains($permission, '?');
    }

    private function isSystemPermission(string $permission): bool
    {
        $systemPermissions = $this->config['system_permissions'] ?? [];

        foreach ($systemPermissions as $systemPerm) {
            if ($this->matchesWildcard($systemPerm, $permission)) {
                return true;
            }
        }

        return false;
    }

    private function slugToName(string $slug): string
    {
        return ucwords(str_replace(['.', '_', '-'], ' ', $slug));
    }

    private function getUserCacheKey(UserInterface $user, string $suffix): string
    {
        return "user_{$user->getId()}_{$suffix}";
    }

    private function clearUserCache(UserInterface $user): void
    {
        $prefix = "user_{$user->getId()}_";

        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->cache[$key]);
            }
        }
    }
}