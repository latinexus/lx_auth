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

class AuthManager
{
    private DatabaseDriverInterface $driver;
    private array $config;
    private ?UserInterface $currentUser = null;
    private array $throttle = [];

    private JwtManager $jwtManager;
    private SessionManager $sessionManager;

    public function __construct(
        DatabaseDriverInterface $driver,
        array $config,
        ?JwtManager $jwtManager = null,
        ?SessionManager $sessionManager = null
    ) {
        $this->driver = $driver;
        $this->config = $config;
        $this->jwtManager = $jwtManager ?? new JwtManager($driver, $config);
        $this->sessionManager = $sessionManager ?? new SessionManager($driver, $config);
    }

    // ========== MÉTODOS DE AUTENTICACIÓN ==========

    public function authenticate(array $credentials): ?UserInterface
    {
        $email = $credentials['email'] ?? '';

        if ($this->isThrottled($email)) {
            throw new AuthenticationException('Demasiados intentos fallidos. Por favor, espere.');
        }

        $user = $this->driver->findUserByCredentials($credentials);

        if (!$user) {
            $this->logFailedAttempt($email);
            throw new AuthenticationException('Credenciales inválidas');
        }

        if (!$user->isActive()) {
            throw new AuthenticationException('Usuario inactivo');
        }

        if (!$user->verifyPassword($credentials['password'])) {
            $this->logFailedAttempt($email);
            throw new AuthenticationException('Credenciales inválidas');
        }

        $this->resetThrottle($email);
        $user->updateLastLogin();
        $this->currentUser = $user;

        return $user;
    }

    public function register(array $data): UserInterface
    {
        if (empty($data['email']) || empty($data['password'])) {
            throw new \InvalidArgumentException('Email y contraseña son requeridos');
        }

        if ($this->driver->userExists($data['email'])) {
            throw new AuthenticationException('El email ya está registrado');
        }

        $userData = [
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

    public function login(UserInterface $user, bool $remember = false): void
    {
        $this->currentUser = $user;
        $this->sessionManager->login($user, $remember);
    }

    public function logout(): void
    {
        $this->currentUser = null;
        $this->sessionManager->logout();
    }

    public function getSessionUser(): ?UserInterface
    {
        return $this->sessionManager->getUser();
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    // ========== MÉTODOS JWT ==========

    public function createToken(UserInterface $user, string $name = 'default', array $claims = []): string
    {
        return $this->jwtManager->createToken($user, $name, $claims);
    }

    public function validateToken(string $token): ?UserInterface
    {
        $user = $this->jwtManager->validateToken($token);

        if (!$user || !$user->isActive()) {
            return null;
        }

        $this->currentUser = $user;
        return $user;
    }

    public function createRefreshToken(UserInterface $user): string
    {
        return $this->jwtManager->createRefreshToken($user);
    }

    public function getJwtManager(): JwtManager
    {
        return $this->jwtManager;
    }

    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    // ========== THROTTLING ==========

    private function isThrottled(string $email): bool
    {
        if (!($this->config['throttling']['enabled'] ?? false)) {
            return false;
        }

        $key = $email;
        $maxAttempts = $this->config['throttling']['max_attempts'] ?? 5;
        $lockoutTime = $this->config['throttling']['lockout_time'] ?? 300;

        if (!isset($this->throttle[$key])) {
            return false;
        }

        $attempts = $this->throttle[$key]['attempts'] ?? 0;
        $lastAttempt = $this->throttle[$key]['last_attempt'] ?? 0;

        return $attempts >= $maxAttempts && (time() - $lastAttempt) < $lockoutTime;
    }

    private function logFailedAttempt(string $email): void
    {
        if (!($this->config['throttling']['enabled'] ?? false)) {
            return;
        }

        $key = $email;

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

    private function resetThrottle(string $email): void
    {
        if (!($this->config['throttling']['enabled'] ?? false)) {
            return;
        }

        unset($this->throttle[$email]);
    }

    // ========== GETTERS ==========

    public function user(): ?UserInterface
    {
        if ($this->currentUser !== null) {
            return $this->currentUser;
        }

        $this->currentUser = $this->sessionManager->getUser();
        return $this->currentUser;
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

    public function validatePasswordResetToken(string $token, string $email): bool
    {
        $user = $this->driver->findUserByCredentials(['email' => $email]);

        if (!$user) {
            return false;
        }

        return strlen($token) === 64 && ctype_xdigit($token);
    }

    public function sendActivationEmail(UserInterface $user): bool
    {
        if (!($this->config['require_activation'] ?? false)) {
            return true;
        }

        return true;
    }
}
