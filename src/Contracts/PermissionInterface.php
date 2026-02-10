<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:27
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Contracts;

interface PermissionInterface
{
    public function getId(): int|string;

    public function getSlug(): string;

    public function getName(): string;

    public function getDescription(): ?string;

    public function getTenantId(): ?string;

    public function isWildcard(): bool;

    public function matches(string $permission): bool;

    public function isSystemPermission(): bool;
}