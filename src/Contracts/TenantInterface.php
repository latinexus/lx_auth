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

interface TenantInterface
{
    public function getId(): string;

    public function getName(): string;

    public function getDomain(): ?string;

    public function getConfig(): array;

    public function isActive(): bool;

    public function getCreatedAt(): \DateTimeInterface;

    public function getUpdatedAt(): \DateTimeInterface;
}