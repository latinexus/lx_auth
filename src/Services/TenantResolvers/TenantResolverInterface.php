<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:19
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services\TenantResolvers;

/**
 * Interfaz para resolvers de tenant
 */
interface TenantResolverInterface
{
    /**
     * Resuelve el identificador del tenant
     */
    public function resolve(): ?string;

    /**
     * Prioridad del resolver (mayor = más prioritario)
     */
    public function getPriority(): int;
}