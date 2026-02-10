<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:28
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services\TenantResolvers;

/**
 * Resuelve tenant por dominio completo (no solo subdominio)
 */
class DomainResolver implements TenantResolverInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function resolve(): ?string
    {
        if (!isset($_SERVER['HTTP_HOST'])) {
            return null;
        }

        $host = $_SERVER['HTTP_HOST'];

        // Buscar en la lista de tenants por dominio
        // Esto requeriría una búsqueda en base de datos

        return $host; // O podríamos devolver el dominio completo
    }

    public function getPriority(): int
    {
        return 95;
    }
}