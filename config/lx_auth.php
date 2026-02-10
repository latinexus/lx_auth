<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:07
 * Proyecto: cp_lx_auth
 */


return [
    /*
    |--------------------------------------------------------------------------
    | Configuración por defecto
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'driver' => 'eloquent',
        'tenant_resolver' => 'subdomain',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de autenticación
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'model' => \LxAuth\Models\User::class,
        'password_hash' => 'bcrypt',
        'require_activation' => false,
        'activation_expire' => 86400, // 24 horas
        'password_reset_expire' => 3600, // 1 hora

        // Throttling
        'throttling' => [
            'enabled' => true,
            'max_attempts' => 5,
            'lockout_time' => 300, // 5 minutos
            'ip_banning' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de multi-tenancy
    |--------------------------------------------------------------------------
    */
    'tenancy' => [
        'model' => \LxAuth\Models\Tenant::class,
        'resolver' => 'subdomain', // Resolver por defecto

        // Resolvers disponibles (en orden de prioridad)
        'resolvers' => [
            'subdomain' => \LxAuth\Services\TenantResolvers\SubdomainResolver::class,
            'domain' => \LxAuth\Services\TenantResolvers\DomainResolver::class,
            'header' => \LxAuth\Services\TenantResolvers\HeaderResolver::class,
            'jwt' => \LxAuth\Services\TenantResolvers\JWTClaimResolver::class,
            'path' => \LxAuth\Services\TenantResolvers\PathResolver::class,
        ],

        // Configuración específica por resolver
        'header_name' => 'X-Tenant-ID', // Para HeaderResolver
        'jwt_header' => 'Authorization', // Para JWTClaimResolver

        // Rutas reservadas (no consideradas como tenants)
        'reserved_paths' => ['auth', 'api', 'admin', 'static', 'assets', 'css', 'js', 'img'],

        // Dominios permitidos para subdominios
        'allowed_domains' => [
            'localhost',
            '127.0.0.1',
        ],

        // Tenant por defecto (para rutas sin tenant)
        'default_tenant_id' => null,

        // Columna de tenant en las tablas
        'tenant_column' => 'tenant_id',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de roles
    |--------------------------------------------------------------------------
    */
    'roles' => [
        'model' => \LxAuth\Models\Role::class,
        'hierarchical' => true,
        'cache_enabled' => true,
        'cache_ttl' => 3600,

        // Roles por defecto
        'default' => 'user',

        // Roles del sistema (no eliminables)
        'system_roles' => [
            'super_admin',
            'system',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de permisos
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'model' => \LxAuth\Models\Permission::class,
        'wildcard_enabled' => true,
        'cache_enabled' => true,
        'cache_ttl' => 3600,

        // Delimitadores para wildcards
        'wildcard_delimiters' => ['.', ':'],

        // Permisos del sistema
        'system_permissions' => [
            '*',
            'system.*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de drivers
    |--------------------------------------------------------------------------
    */
    'drivers' => [
        'eloquent' => [
            'connection' => null, // null = conexión por defecto
            'prefix' => '',
            'models' => [
                'user' => \LxAuth\Models\User::class,
                'role' => \LxAuth\Models\Role::class,
                'permission' => \LxAuth\Models\Permission::class,
                'tenant' => \LxAuth\Models\Tenant::class,
            ],
        ],

        // Futuros drivers
        // 'redis' => [
        //     'connection' => 'default',
        //     'prefix' => 'lx_auth:',
        //     'serializer' => 'php',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de tokens
    |--------------------------------------------------------------------------
    */
    'tokens' => [
        'jwt' => [
            'secret' => env('LX_AUTH_JWT_SECRET', 'your-secret-key-change-in-production'),
            'algorithm' => 'HS256',
            'ttl' => 3600, // 1 hora
            'refresh_ttl' => 2592000, // 30 días
            'leeway' => 60, // 60 segundos de margen
        ],

        'api' => [
            'length' => 60,
            'prefix' => 'lx_',
            'expire' => 86400, // 24 horas
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de sesiones
    |--------------------------------------------------------------------------
    */
    'session' => [
        'driver' => 'native', // native, database, redis
        'lifetime' => 120, // minutos
        'encrypt' => true,
        'same_site' => 'lax',
        'http_only' => true,
        'secure' => env('APP_ENV') === 'production',

        // Sesiones multi-tenant
        'tenant_aware' => true,
        'tenant_session_key' => 'lx_auth_tenant',
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de rutas
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'prefix' => 'auth',
        'middleware' => ['web'],
        'namespace' => 'LxAuth\Http\Controllers',

        // Habilitar/deshabilitar rutas
        'enable_registration' => true,
        'enable_password_reset' => true,
        'enable_email_verification' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de eventos
    |--------------------------------------------------------------------------
    */
    'events' => [
        'enabled' => true,

        // Eventos disponibles
        'user_registered' => true,
        'user_logged_in' => true,
        'user_logged_out' => true,
        'password_reset' => true,
        'role_assigned' => true,
        'permission_granted' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => true,
        'channel' => env('LX_AUTH_LOG_CHANNEL', 'stack'),

        // Eventos a loguear
        'log_auth_attempts' => true,
        'log_role_changes' => true,
        'log_permission_changes' => true,
        'log_tenant_changes' => true,
    ],
];



