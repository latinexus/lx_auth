<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 03/07/26
 * Proyecto: cp_lx_auth
 */

namespace LxAuth\Services;

use LxAuth\Contracts\UserInterface;
use LxAuth\Drivers\Database\DatabaseDriverInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtManager
{
    private DatabaseDriverInterface $driver;
    private array $config;

    public function __construct(DatabaseDriverInterface $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config['tokens']['jwt'] ?? [];

        // Sincronizar leeway de JWT si está configurado
        if (isset($this->config['leeway'])) {
            JWT::$leeway = (int) $this->config['leeway'];
        }
    }

    public function createToken(UserInterface $user, string $name = 'default', array $claims = []): string
    {
        if (empty($this->config['secret'])) {
            throw new \RuntimeException('JWT secret no configurado');
        }

        $alg = $this->config['algorithm'] ?? 'HS256';
        $this->ensureSecretLength($this->config['secret'], $alg);

        $payload = array_merge([
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + ($this->config['ttl'] ?? 3600),
            'jti' => bin2hex(random_bytes(16)),
            'sub' => $user->getId(),
            'type' => 'access',
            'name' => $name,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
            ],
        ], $claims);

        return JWT::encode($payload, $this->config['secret'], $alg);
    }

    public function validateToken(string $token): ?UserInterface
    {
        if (empty($this->config['secret'])) {
            throw new \RuntimeException('JWT secret no configurado');
        }

        $alg = $this->config['algorithm'] ?? 'HS256';
        $this->ensureSecretLength($this->config['secret'], $alg);

        try {
            $decoded = JWT::decode(
                $token,
                new Key($this->config['secret'], $alg)
            );

            if (empty($decoded->sub)) {
                return null;
            }

            $user = $this->driver->findUserById($decoded->sub);

            if (!$user || !$user->isActive()) {
                return null;
            }

            return $user;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function createRefreshToken(UserInterface $user): string
    {
        $alg = $this->config['algorithm'] ?? 'HS256';

        if (empty($this->config['secret'])) {
            throw new \RuntimeException('JWT secret no configurado');
        }

        $this->ensureSecretLength($this->config['secret'], $alg);

        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'iat' => time(),
            'exp' => time() + ($this->config['refresh_ttl'] ?? 2592000),
            'jti' => bin2hex(random_bytes(16)),
            'sub' => $user->getId(),
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $this->config['secret'], $alg);
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    private function ensureSecretLength(string $secret, string $alg): void
    {
        if (str_starts_with($alg, 'HS')) {
            $bits = (int) str_replace('HS', '', $alg);
            $minBytes = (int) ceil($bits / 8);
            if (strlen($secret) < $minBytes) {
                throw new \RuntimeException(sprintf(
                    'JWT secret too short for %s: need at least %d bytes (%d bits), current %d bytes',
                    $alg,
                    $minBytes,
                    $bits,
                    strlen($secret)
                ));
            }
        }
    }
}
