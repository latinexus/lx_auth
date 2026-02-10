<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:27
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services\TenantResolvers;

/**
 * Resuelve tenant desde la ruta URL
 */
class PathResolver implements TenantResolverInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function resolve(): ?string
    {
        if (!isset($_SERVER['REQUEST_URI'])) {
            return null;
        }

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = trim($path, '/');

        if (empty($path)) {
            return null;
        }

        $segments = explode('/', $path);

        // El primer segmento podría ser el tenant
        if (!empty($segments[0])) {
            $possibleTenant = $segments[0];

            // Validar que no sea una ruta reservada
            $reservedPaths = $this->config['reserved_paths'] ?? ['auth', 'api', 'admin', 'static', 'assets'];

            if (!in_array($possibleTenant, $reservedPaths, true)) {
                return $possibleTenant;
            }
        }

        return null;
    }

    public function getPriority(): int
    {
        return 70;
    }
}


