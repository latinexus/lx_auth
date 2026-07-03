<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:01
 * Proyecto: cp_lx_auth
 */


namespace LxAuth;

use LxAuth\Contracts\UserInterface;
use LxAuth\Contracts\RoleInterface;
use LxAuth\Services\AuthManager;
use LxAuth\Services\RoleManager;
use LxAuth\Services\PermissionManager;
use LxAuth\Drivers\Database\EloquentDriver;
use LxAuth\Exceptions\AuthenticationException;
use LxAuth\Exceptions\PermissionDeniedException;
use LxAuth\Middleware\Authenticate;
use LxAuth\Middleware\PermissionMiddleware;
use LxAuth\Middleware\RoleMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Container\ContainerInterface;

/**
 * Clase principal del sistema de autenticación LX
 */
class LxAuth
{
    private static ?LxAuth $instance = null;

    private AuthManager $authManager;
    private RoleManager $roleManager;
    private PermissionManager $permissionManager;
    private array $config;
    private ?ContainerInterface $container = null;

    /**
     * Constructor privado para singleton
     */
    private function __construct(array $config = [], ?ContainerInterface $container = null)
    {
        $this->config = $this->mergeDefaultConfig($config);
        $this->container = $container;
        $this->initializeServices();
    }

    /**
     * Obtiene la instancia singleton
     */
    public static function getInstance(array $config = [], ?ContainerInterface $container = null): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config, $container);
        }

        return self::$instance;
    }

    /**
     * Inicializa todos los servicios
     */
    private function initializeServices(): void
    {
        // Inicializar driver de base de datos
        $driver = $this->createDriver();

        // Fusionar config de auth con tokens y session para AuthManager
        $authConfig = array_merge($this->config['auth'], [
            'tokens' => $this->config['tokens'] ?? [],
            'session' => $this->config['session'] ?? [],
        ]);

        // Inicializar servicios
        $this->authManager = new AuthManager($driver, $authConfig);
        $this->roleManager = new RoleManager($driver, $this->config['roles']);
        $this->permissionManager = new PermissionManager($driver, $this->config['permissions']);
    }

    /**
     * Crea el driver basado en la configuración
     */
    private function createDriver()
    {
        $driverType = $this->config['defaults']['driver'] ?? 'eloquent';

        switch ($driverType) {
            case 'eloquent':
                return new EloquentDriver($this->config['drivers']['eloquent']);
            // Aquí se pueden añadir más drivers en el futuro
            // case 'redis':
            //     return new RedisDriver($this->config['drivers']['redis']);
            default:
                throw new \InvalidArgumentException("Driver no soportado: {$driverType}");
        }
    }

    /**
     * Fusiona la configuración con los valores por defecto
     */
    private function mergeDefaultConfig(array $config): array
    {
        $defaults = [
            'defaults' => [
                'driver' => 'eloquent',
            ],
            'auth' => [
                'password_hash' => 'bcrypt',
                'require_activation' => false,
                'throttling' => [
                    'enabled' => true,
                    'max_attempts' => 5,
                    'lockout_time' => 300,
                ],
            ],
            'roles' => [
                'hierarchical' => true,
                'cache_enabled' => true,
                'cache_ttl' => 3600,
            ],
            'permissions' => [
                'wildcard_enabled' => true,
                'cache_enabled' => true,
                'cache_ttl' => 3600,
            ],
            'drivers' => [
                'eloquent' => [
                    'connection' => null,
                    'prefix' => '',
                ],
            ],
            'tokens' => [
                'jwt' => [
                    'secret' => '',
                    'algorithm' => 'HS256',
                    'ttl' => 3600,
                    'refresh_ttl' => 2592000,
                    'leeway' => 60,
                ],
            ],
        ];

        return array_replace_recursive($defaults, $config);
    }

    /**
     * Autentica un usuario con credenciales
     */
    public function authenticate(array $credentials): ?UserInterface
    {
        return $this->authManager->authenticate($credentials);
    }

    /**
     * Registra un nuevo usuario
     */
    public function register(array $data): UserInterface
    {
        return $this->authManager->register($data);
    }

    /**
     * Obtiene el usuario actualmente autenticado
     */
    public function user(): ?UserInterface
    {
        return $this->authManager->user();
    }

    /**
     * Verifica si el usuario actual tiene un permiso
     */
    public function can(string $permission, ?array $context = null): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $this->permissionManager->userCan($user, $permission, $context);
    }

    /**
     * Verifica si el usuario actual tiene un rol
     */
    public function hasRole(string $role): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        return $this->roleManager->userHasRole($user, $role);
    }

    /**
     * Asigna un rol a un usuario
     */
    public function assignRole(UserInterface $user, string $role): void
    {
        $this->roleManager->assignRole($user, $role);
    }

    /**
     * Remueve un rol de un usuario
     */
    public function removeRole(UserInterface $user, string $role): void
    {
        $this->roleManager->removeRole($user, $role);
    }

    /**
     * Otorga un permiso directo a un usuario
     */
    public function givePermissionTo(UserInterface $user, string $permission, bool $grant = true): void
    {
        $this->permissionManager->givePermissionTo($user, $permission, $grant);
    }

    /**
     * Crea un token JWT para un usuario
     */
    public function createToken(UserInterface $user, string $name = 'default', array $claims = []): string
    {
        return $this->authManager->createToken($user, $name, $claims);
    }

    /**
     * Valida un token JWT
     */
    public function validateToken(string $token): ?UserInterface
    {
        return $this->authManager->validateToken($token);
    }

    /**
     * Middleware de autenticación
     */
    public function middleware(array $except = []): Authenticate
    {
        return new Authenticate($this, $except);
    }

    /**
     * Middleware de permisos
     */
    public function permissionMiddleware(string $permission, ?array $context = null): PermissionMiddleware
    {
        return new PermissionMiddleware($this, $permission, $context);
    }

    /**
     * Middleware de roles
     */
    public function roleMiddleware(string $role): RoleMiddleware
    {
        return new RoleMiddleware($this, $role);
    }

    /**
     * Obtiene la configuración
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Obtiene el administrador de autenticación
     */
    public function getAuthManager(): AuthManager
    {
        return $this->authManager;
    }

    /**
     * Obtiene el administrador de roles
     */
    public function getRoleManager(): RoleManager
    {
        return $this->roleManager;
    }

    /**
     * Obtiene el administrador de permisos
     */
    public function getPermissionManager(): PermissionManager
    {
        return $this->permissionManager;
    }

    /**
     * Obtiene el contenedor de dependencias
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * Establece el contenedor de dependencias
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Inicia sesión para un usuario
     */
    // src/LxAuth.php
    public function logout(): void
    {
        $this->authManager->logout();
    }

    public function login(UserInterface $user, bool $remember = false): void
    {
        $this->authManager->login($user, $remember);
    }

    public function check(): bool
    {
        return $this->authManager->check();
    }

    /**
     * Obtiene usuario desde sesión
     */
    public function sessionUser(): ?UserInterface
    {
        return $this->authManager->getSessionUser();
    }
}