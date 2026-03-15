<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:06
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Services;

use LxAuth\Contracts\UserInterface;
use LxAuth\Drivers\Database\DatabaseDriverInterface;
use LxAuth\Exceptions\AuthenticationException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthManager
{
    private DatabaseDriverInterface $driver;
    private array $config;
    private ?UserInterface $currentUser = null;
    private array $throttle = [];
    private bool $sessionStarted = false;

    public function __construct(DatabaseDriverInterface $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config;

        // Sincronizar leeway de JWT si está configurado
        $jwtConfig = $this->config['tokens']['jwt'] ?? [];
        if (isset($jwtConfig['leeway'])) {
            JWT::$leeway = (int) $jwtConfig['leeway'];
        }
    }

    // ========== MÉTODOS DE AUTENTICACIÓN ==========

    public function authenticate(array $credentials, string $tenantId): ?UserInterface
    {
        if ($this->isThrottled($credentials['email'] ?? '', $tenantId)) {
            throw new AuthenticationException('Demasiados intentos fallidos. Por favor, espere.');
        }

        $user = $this->driver->findUserByCredentials($credentials, $tenantId);

        if (!$user) {
            $this->logFailedAttempt($credentials['email'] ?? '', $tenantId);
            throw new AuthenticationException('Credenciales inválidas');
        }

        if (!$user->isActive()) {
            throw new AuthenticationException('Usuario inactivo');
        }

        if (!$user->verifyPassword($credentials['password'])) {
            $this->logFailedAttempt($credentials['email'], $tenantId);
            throw new AuthenticationException('Credenciales inválidas');
        }

        $this->resetThrottle($credentials['email'], $tenantId);
        $user->updateLastLogin();
        $this->currentUser = $user;

        return $user;
    }

    public function register(array $data, string $tenantId): UserInterface
    {
        if (empty($data['email']) || empty($data['password'])) {
            throw new \InvalidArgumentException('Email y contraseña son requeridos');
        }

        if ($this->driver->userExists($data['email'], $tenantId)) {
            throw new AuthenticationException('El email ya está registrado');
        }

        $userData = [
            'tenant_id' => $tenantId,
            'email' => $data['email'],
            'password' => $data['password'],
            'first_name' => $data['first_name'] ?? null,
            'last_name' => $data['last_name'] ?? null,
            'is_active' => !($this->config['require_activation'] ?? false),
            'permissions' => $data['permissions'] ?? [],
            'meta' => $data['meta'] ?? [],
        ];

        $user = $this->driver->createUser($userData);

        if (isset($data['role'])) {
            $this->driver->assignRole($user, $data['role']);
        }

        return $user;
    }

    // ========== MÉTODOS DE SESIÓN ==========

    /**
     * Inicia sesión para un usuario
     */
    public function login(UserInterface $user, bool $remember = false): void
    {
        $this->currentUser = $user;

        // Iniciar sesión PHP si está configurado (driver 'native')
        if (($this->config['session']['driver'] ?? 'native') === 'native') {
            $this->startSession();

            // Configurar datos de sesión multi-tenant
            $sessionData = [
                'user_id' => $user->getId(),
                'tenant_id' => $user->getTenantId(),
                'email' => $user->getEmail(),
                'logged_in_at' => time(),
                'remember' => $remember
            ];

            // Si es tenant-aware, guardar tenant en clave separada
            if ($this->config['session']['tenant_aware'] ?? true) {
                $tenantKey = $this->config['session']['tenant_session_key'] ?? 'lx_auth_tenant';
                $_SESSION[$tenantKey] = $user->getTenantId();
            }

            $_SESSION['lx_auth'] = $sessionData;

            // Configurar cookie de larga duración para "remember me"
            if ($remember) {
                $lifetime = $this->config['session']['remember_lifetime'] ?? 2592000; // 30 días
                $params = session_get_cookie_params();

                setcookie(
                    session_name(),
                    session_id(),
                    time() + $lifetime,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }

            session_regenerate_id(true);
        }
        // Para drivers 'database' o 'redis' necesitarías implementación adicional
    }

    /**
     * Cierra sesión (ambos: JWT y sesión PHP)
     */
    public function logout(): void
    {
        // Limpiar usuario actual
        $this->currentUser = null;

        // Cerrar sesión PHP si existe
        if (($this->config['session']['driver'] ?? 'native') === 'native' && $this->sessionStarted) {
            $this->destroySession();
        }
    }

    /**
     * Inicia sesión PHP
     */
    private function startSession(): void
    {
        if (!$this->sessionStarted && session_status() === PHP_SESSION_NONE) {
            $sessionConfig = [];

            // Configurar opciones de sesión desde configuración
            $sessionName = $this->config['session']['name'] ?? session_name();
            if ($sessionName) {
                session_name($sessionName);
            }

            // Configurar cookie de sesión
            $cookieParams = [
                'lifetime' => ($this->config['session']['lifetime'] ?? 120) * 60, // convertir minutos a segundos
                'path' => '/',
                'domain' => '',
                'secure' => $this->config['session']['secure'] ?? false,
                'httponly' => $this->config['session']['http_only'] ?? true,
                'samesite' => $this->config['session']['same_site'] ?? 'Lax'
            ];

            session_set_cookie_params($cookieParams);

            // Opciones adicionales de session_start
            $sessionOptions = [];
            if ($this->config['session']['encrypt'] ?? false) {
                ini_set('session.use_cookies', 1);
                ini_set('session.use_only_cookies', 1);
            }

            session_start($sessionOptions);
            $this->sessionStarted = true;
        }
    }

    /**
     * Destruye sesión PHP
     */
    private function destroySession(): void
    {
        if ($this->sessionStarted) {
            // Limpiar datos de sesión multi-tenant
            if ($this->config['session']['tenant_aware'] ?? true) {
                $tenantKey = $this->config['session']['tenant_session_key'] ?? 'lx_auth_tenant';
                unset($_SESSION[$tenantKey]);
            }

            unset($_SESSION['lx_auth']);
            session_destroy();
            $this->sessionStarted = false;

            // Limpiar cookie
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
    }

    /**
     * Obtiene usuario desde sesión PHP
     */
    public function getSessionUser(): ?UserInterface
    {
        $sessionDriver = $this->config['session']['driver'] ?? 'native';

        // Solo soportamos 'native' por ahora
        if ($sessionDriver !== 'native') {
            return null;
        }

        $this->startSession();

        if (!isset($_SESSION['lx_auth'])) {
            return null;
        }

        $authData = $_SESSION['lx_auth'];

        // Verificar expiración de sesión
        $sessionLifetime = ($this->config['session']['lifetime'] ?? 120) * 60; // minutos a segundos

        if ((time() - $authData['logged_in_at']) > $sessionLifetime) {
            // Si no es "remember me", destruir sesión
            if (!($authData['remember'] ?? false)) {
                $this->destroySession();
                return null;
            }
        }

        // Verificar expiración de "remember me"
        if (($authData['remember'] ?? false)) {
            $rememberLifetime = $this->config['session']['remember_lifetime'] ?? 2592000;
            if ((time() - $authData['logged_in_at']) > $rememberLifetime) {
                $this->destroySession();
                return null;
            }
        }

        return $this->driver->findUserById(
            $authData['user_id'],
            $authData['tenant_id']
        );
    }

    /**
     * Verifica si hay sesión activa
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Obtiene el tenant desde la sesión
     */
    public function getSessionTenant(): ?string
    {
        $sessionDriver = $this->config['session']['driver'] ?? 'native';

        if ($sessionDriver !== 'native') {
            return null;
        }

        $this->startSession();

        if ($this->config['session']['tenant_aware'] ?? true) {
            $tenantKey = $this->config['session']['tenant_session_key'] ?? 'lx_auth_tenant';
            return $_SESSION[$tenantKey] ?? null;
        }

        return $_SESSION['lx_auth']['tenant_id'] ?? null;
    }

    // ========== MÉTODOS JWT ==========

    public function createToken(UserInterface $user, string $name = 'default', array $claims = []): string
    {
        $jwtConfig = $this->config['tokens']['jwt'] ?? [];

        if (empty($jwtConfig['secret'])) {
            throw new \RuntimeException('JWT secret no configurado');
        }

        $alg = $jwtConfig['algorithm'] ?? 'HS256';
        $this->ensureJwtSecretLength($jwtConfig['secret'], $alg);

        $payload = array_merge([
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $user->getTenantId(),
            'iat' => time(),
            'exp' => time() + ($jwtConfig['ttl'] ?? 3600),
            'jti' => bin2hex(random_bytes(16)),
            'sub' => $user->getId(),
            'type' => 'access',
            'name' => $name,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'tenant_id' => $user->getTenantId(),
            ],
        ], $claims);

        return JWT::encode($payload, $jwtConfig['secret'], $alg);
    }

    public function validateToken(string $token): ?UserInterface
    {
        $jwtConfig = $this->config['tokens']['jwt'] ?? [];

        if (empty($jwtConfig['secret'])) {
            throw new \RuntimeException('JWT secret no configurado');
        }

        $alg = $jwtConfig['algorithm'] ?? 'HS256';
        $this->ensureJwtSecretLength($jwtConfig['secret'], $alg);

        try {
            $decoded = JWT::decode(
                $token,
                new Key($jwtConfig['secret'], $alg)
            );

            if (empty($decoded->sub) || empty($decoded->user->tenant_id)) {
                return null;
            }

            $user = $this->driver->findUserById($decoded->sub, $decoded->user->tenant_id);

            if (!$user || !$user->isActive()) {
                return null;
            }

            $this->currentUser = $user;
            return $user;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function createRefreshToken(UserInterface $user): string
    {
        $jwtConfig = $this->config['tokens']['jwt'] ?? [];

        $alg = $jwtConfig['algorithm'] ?? 'HS256';
        if (empty($jwtConfig['secret'])) {
            throw new \RuntimeException('JWT secret no configurado');
        }
        $this->ensureJwtSecretLength($jwtConfig['secret'], $alg);

        $payload = [
            'iss' => $_SERVER['HTTP_HOST'] ?? 'localhost',
            'aud' => $user->getTenantId(),
            'iat' => time(),
            'exp' => time() + ($jwtConfig['refresh_ttl'] ?? 2592000),
            'jti' => bin2hex(random_bytes(16)),
            'sub' => $user->getId(),
            'type' => 'refresh',
        ];

        return JWT::encode($payload, $jwtConfig['secret'], $alg);
    }

    /**
     * Ensure JWT secret length is sufficient for HS* algorithms.
     */
    private function ensureJwtSecretLength(string $secret, string $alg): void
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

    // ========== THROTTLING ==========

    private function isThrottled(string $email, string $tenantId): bool
    {
        if (!($this->config['throttling']['enabled'] ?? false)) {
            return false;
        }

        $key = "{$tenantId}:{$email}";
        $maxAttempts = $this->config['throttling']['max_attempts'] ?? 5;
        $lockoutTime = $this->config['throttling']['lockout_time'] ?? 300;

        if (!isset($this->throttle[$key])) {
            return false;
        }

        $attempts = $this->throttle[$key]['attempts'] ?? 0;
        $lastAttempt = $this->throttle[$key]['last_attempt'] ?? 0;

        return $attempts >= $maxAttempts && (time() - $lastAttempt) < $lockoutTime;
    }

    private function logFailedAttempt(string $email, string $tenantId): void
    {
        if (!($this->config['throttling']['enabled'] ?? false)) {
            return;
        }

        $key = "{$tenantId}:{$email}";

        if (!isset($this->throttle[$key])) {
            $this->throttle[$key] = [
                'attempts' => 1,
                'last_attempt' => time(),
            ];
        } else {
            $this->throttle[$key]['attempts']++;
            $this->throttle[$key]['last_attempt'] = time();
        }
    }

    private function resetThrottle(string $email, string $tenantId): void
    {
        if (!($this->config['throttling']['enabled'] ?? false)) {
            return;
        }

        $key = "{$tenantId}:{$email}";
        unset($this->throttle[$key]);
    }

    // ========== GETTERS ==========

    public function user(): ?UserInterface
    {
        // Devolver usuario actual si ya está establecido
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        // Intentar obtener de sesión PHP (solo driver 'native')
        if (($this->config['session']['driver'] ?? 'native') === 'native') {
            $this->currentUser = $this->getSessionUser();
            if ($this->currentUser !== null) {
                return $this->currentUser;
            }
        }

        return null;
    }

    public function getDriver(): DatabaseDriverInterface
    {
        return $this->driver;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    // ========== MÉTODOS DE VALIDACIÓN ==========

    /**
     * Valida un token de recuperación de contraseña
     */
    public function validatePasswordResetToken(string $token, string $email, string $tenantId): bool
    {
        // Implementación básica - puedes mejorarla
        $user = $this->driver->findUserByCredentials(['email' => $email], $tenantId);

        if (!$user) {
            return false;
        }

        // Aquí podrías verificar el token en la base de datos
        // Por ahora, solo validación básica
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    /**
     * Envia email de activación
     */
    public function sendActivationEmail(UserInterface $user): bool
    {
        if (!($this->config['require_activation'] ?? false)) {
            return true;
        }

        // Implementación básica - debes implementar el envío real
        $token = bin2hex(random_bytes(32));

        // Guardar token en base de datos o cache
        // Enviar email con enlace de activación

        return true;
    }
}
