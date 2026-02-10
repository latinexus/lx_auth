<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:17
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services;

use LxAuth\Services\TenantResolvers\TenantResolverInterface;
use LxAuth\Exceptions\TenantResolveException;

/**
 * Servicio para resolver el tenant actual
 */
class TenantResolver
{
    private array $resolvers = [];
    private ?string $currentTenant = null;
    private ?string $defaultTenant = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->setupResolvers();
    }

    private function setupResolvers(): void
    {
        $resolverClasses = $this->config['resolvers'] ?? [];

        foreach ($resolverClasses as $type => $className) {
            if (class_exists($className)) {
                $this->resolvers[$type] = new $className($this->config);
            }
        }

        $this->defaultTenant = $this->config['default_tenant_id'] ?? null;
    }

    /**
     * Resuelve el tenant actual
     */

    // En src/Services/TenantResolver.php, modifica el método resolve():
    public function resolve(): ?string
    {
        if ($this->currentTenant !== null) {
            return $this->currentTenant;
        }

        $resolverType = $this->config['resolver'] ?? 'subdomain';

        // Opción 1: Solo usar el resolver especificado
        if (isset($this->resolvers[$resolverType])) {
            $resolver = $this->resolvers[$resolverType];
            $tenantId = $resolver->resolve();
        } else {
            throw new TenantResolveException("Resolver '{$resolverType}' no disponible");
        }

        // Opción 2: Intentar todos los resolvers en orden de prioridad
        /*
        $tenantId = null;
        $resolvers = $this->getResolversByPriority();

        foreach ($resolvers as $resolver) {
            $tenantId = $resolver->resolve();
            if ($tenantId !== null) {
                break;
            }
        }
        */

        if ($tenantId === null && $this->defaultTenant !== null) {
            $tenantId = $this->defaultTenant;
        }

        $this->currentTenant = $tenantId;
        return $tenantId;
    }

    /**
     * Obtiene resolvers ordenados por prioridad
     */
    private function getResolversByPriority(): array
    {
        $resolvers = [];
        foreach ($this->resolvers as $resolver) {
            $resolvers[$resolver->getPriority()] = $resolver;
        }

        krsort($resolvers); // Orden descendente por prioridad
        return $resolvers;
    }

    /**
     * Establece manualmente el tenant actual
     */
    public function setCurrentTenant(string $tenantId): void
    {
        $this->currentTenant = $tenantId;
    }

    /**
     * Obtiene el tenant actual
     */
    public function getCurrentTenant(): ?string
    {
        return $this->currentTenant;
    }

    /**
     * Establece el tenant por defecto
     */
    public function setDefaultTenant(string $tenantId): void
    {
        $this->defaultTenant = $tenantId;
    }

    /**
     * Obtiene el tenant por defecto
     */
    public function getDefaultTenant(): ?string
    {
        return $this->defaultTenant;
    }

    /**
     * Limpia el tenant cacheado
     */
    public function clear(): void
    {
        $this->currentTenant = null;
    }

    /**
     * Obtiene los tipos de resolvers disponibles
     */
    public function getResolvers(): array
    {
        return array_keys($this->resolvers);
    }

    /**
     * Agrega un resolver personalizado
     */
    public function addResolver(string $type, TenantResolverInterface $resolver): void
    {
        $this->resolvers[$type] = $resolver;
    }

    /**
     * Elimina un resolver
     */
    public function removeResolver(string $type): void
    {
        unset($this->resolvers[$type]);
    }
}


