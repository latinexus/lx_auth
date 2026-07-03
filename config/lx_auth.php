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
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de autenticación
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'password_hash' => 'bcrypt',
        'require_activation' => false,
        'activation_expire' => 86400, // 24 horas
        'password_reset_expire' => 3600, // 1 hora

        // Throttling
        'throttling' => [
            'enabled' => true,
            'max_attempts' => 5,
            'lockout_time' => 300, // 5 minutos
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de roles
    |--------------------------------------------------------------------------
    */
    'roles' => [
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
        ],
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
        'driver' => 'native',
        'lifetime' => 120, // minutos
        'encrypt' => true,
        'same_site' => 'lax',
        'http_only' => true,
        'secure' => env('APP_ENV') === 'production',
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
    ],
];



