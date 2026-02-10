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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Resuelve tenant desde claim JWT
 */
class JWTClaimResolver implements TenantResolverInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function resolve(): ?string
    {
        $jwtConfig = $this->config['tokens']['jwt'] ?? [];

        if (empty($jwtConfig['secret'])) {
            return null;
        }

        // Buscar token en headers
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$authHeader) {
            return null;
        }

        // Extraer token (Bearer token)
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            $token = $authHeader;
        }

        try {
            $decoded = JWT::decode($token, new Key($jwtConfig['secret'], $jwtConfig['algorithm'] ?? 'HS256'));

            // Buscar tenant en claims (en orden de prioridad)
            if (isset($decoded->aud)) {
                return $decoded->aud; // audience claim
            }

            if (isset($decoded->tenant_id)) {
                return $decoded->tenant_id;
            }

            if (isset($decoded->user->tenant_id)) {
                return $decoded->user->tenant_id;
            }

            if (isset($decoded->tenant)) {
                return $decoded->tenant;
            }

        } catch (\Exception $e) {
            // Token inválido o expirado
            return null;
        }

        return null;
    }

    public function getPriority(): int
    {
        return 80;
    }
}

