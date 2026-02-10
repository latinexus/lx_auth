<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:41
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Contracts;

/**
 * Interfaz base para todos los drivers de almacenamiento
 */
interface DriverInterface
{
    public function findUserById($id, string $tenantId): ?UserInterface;

    public function findUserByCredentials(array $credentials, string $tenantId): ?UserInterface;

    public function userExists(string $email, string $tenantId): bool;

    public function createUser(array $data): UserInterface;

    public function updateUser(UserInterface $user, array $data): bool;

    public function deleteUser(UserInterface $user): bool;

    public function assignRole(UserInterface $user, string $role): bool;

    public function removeRole(UserInterface $user, string $role): bool;

    public function getRoles(UserInterface $user): array;
}