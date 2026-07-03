<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:36
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Drivers\Database;

use LxAuth\Contracts\UserInterface;
use LxAuth\Contracts\DriverInterface;

interface DatabaseDriverInterface extends DriverInterface
{
    public function getConnection();

    public function setConnection($connection): void;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function createRole(array $data);

    public function findRoleBySlug(string $slug);

    public function createPermission(array $data);

    public function findPermissionBySlug(string $slug);

    public function getPermissionsForUser(UserInterface $user): array;

    public function getAllPermissions(): array;
}