<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:20
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services\TenantResolvers;

/**
 * Resuelve tenant por cabecera HTTP
 */
class HeaderResolver implements TenantResolverInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function resolve(): ?string
    {
        $headerName = $this->config['header_name'] ?? 'X-Tenant-ID';

        // Buscar en headers HTTP
        $headerKey = 'HTTP_' . strtoupper(str_replace('-', '_', $headerName));
        if (isset($_SERVER[$headerKey])) {
            return $_SERVER[$headerKey];
        }

        return null;
    }

    public function getPriority(): int
    {
        return 90;
    }
}