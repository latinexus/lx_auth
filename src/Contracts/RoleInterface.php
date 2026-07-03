<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:26
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Contracts;

interface RoleInterface
{
    public function getId(): int|string;

    public function getSlug(): string;

    public function getName(): string;

    public function getPermissions(): array;

    public function hasPermission(string $permission): bool;

    public function getParent(): ?RoleInterface;

    public function getChildren(): array;

    public function getLevel(): int;

    public function isSystemRole(): bool;
}