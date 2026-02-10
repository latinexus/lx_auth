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
 * Resuelve tenant por subdominio
 */
class SubdomainResolver implements TenantResolverInterface
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
        $allowedDomains = $this->config['allowed_domains'] ?? [];

        // Verificar si el host está en los dominios permitidos
        foreach ($allowedDomains as $domain) {
            if (str_ends_with($host, '.' . $domain)) {
                $subdomain = str_replace('.' . $domain, '', $host);

                if (empty($subdomain) || $subdomain === 'www') {
                    return null;
                }

                return $subdomain;
            }
        }

        return null;
    }

    public function getPriority(): int
    {
        return 100;
    }
}