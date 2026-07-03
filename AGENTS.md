# LX_AUTH - Guía para Agentes de Código

## ¿Qué es LX_AUTH?

Librería PHP de autenticación y autorización (auth, roles, permisos, JWT, sesiones).
Opera contra **una única base de datos por instancia**. No resuelve tenants ni gestiona conexiones multi-BD.

## Arquitectura

```
LxAuth (singleton)
├── AuthManager
│   ├── JwtManager          → Tokens JWT (crear, validar, refresh)
│   └── SessionManager      → Sesiones PHP (login, logout, getUser)
├── RoleManager             → Roles jerárquicos (asignar, remover, verificar)
├── PermissionManager       → Permisos con wildcards (check, asignar)
└── EloquentDriver          → DatabaseDriverInterface (backend Eloquent)
```

## Integración típica

La aplicación consume LX_AUTH, NO al revés. La app:

1. Resuelve el tenant (subdominio, dominio, etc.)
2. Lee credenciales de conexión para la BD de ese tenant
3. Configura Capsule (Eloquent) con esas credenciales
4. Pasa el Capsule a LX_AUTH
5. LX_AUTH trabaja contra ESA única BD

```php
// Bootstrap de la app
$capsule = new Capsule;
$capsule->addConnection([
    'driver'   => 'mysql',
    'host'     => '...',
    'database' => E_DB_NAM,    // ej: 'cargo_prueba'
    'username' => E_DB_USR,
    'password' => E_DB_PWD,
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// Inicializar LX_AUTH
\LxAuth\Drivers\Database\EloquentDriver::setCapsule($capsule);

$auth = \LxAuth\LxAuth::getInstance([
    'tokens' => ['jwt' => ['secret' => '...']],
]);
```

## Esquema de BD (por tenant)

Cada BD donde opere LX_AUTH debe tener estas tablas (sin tenant_id):

| Tabla | Columnas clave |
|---|---|
| `users` | id, email (unique), password, first_name, last_name, is_active, last_login, permissions (json), meta (json), remember_token, timestamps, soft_deletes |
| `roles` | id, slug (unique), name, description, parent_id, level, is_system, timestamps |
| `permissions` | id, slug (unique), name, description, is_system, is_wildcard, timestamps |
| `role_user` | role_id, user_id (PK compuesta) |
| `permission_role` | permission_id, role_id (PK compuesta) |
| `permission_user` | permission_id, user_id (PK compuesta), grant |
| `persistences` | id, user_id, token, expires_at, ip_address, user_agent, timestamps |

## Contratos (Interfaces)

Todas las operaciones de LX_AUTH dependen de estas interfaces:

- `UserInterface` → getId(), getEmail(), isActive(), getRoles(), hasRole(), verifyPassword(), getDirectPermissions()
- `RoleInterface` → getId(), getSlug(), getName(), getPermissions(), hasPermission(), getParent(), getLevel()
- `PermissionInterface` → getId(), getSlug(), getName(), matches(string $permission), isWildcard()
- `DriverInterface` → CRUD de usuarios + roles + permisos
- `DatabaseDriverInterface` → extiende DriverInterface con transacciones y queries específicas

## API Pública (LxAuth)

```php
$auth->authenticate(['email' => '...', 'password' => '...']): ?UserInterface
$auth->register(['email' => '...', 'password' => '...', ...]): UserInterface
$auth->login(UserInterface $user, bool $remember = false): void
$auth->logout(): void
$auth->user(): ?UserInterface
$auth->check(): bool
$auth->sessionUser(): ?UserInterface
$auth->can(string $permission): bool
$auth->hasRole(string $role): bool
$auth->assignRole(UserInterface $user, string $role): void
$auth->removeRole(UserInterface $user, string $role): void
$auth->givePermissionTo(UserInterface $user, string $permission, bool $grant = true): void
$auth->createToken(UserInterface $user, string $name, array $claims = []): string
$auth->validateToken(string $token): ?UserInterface
$auth->middleware(array $except = []): Authenticate (PSR-15)
$auth->permissionMiddleware(string $permission): PermissionMiddleware (PSR-15)
$auth->roleMiddleware(string $role): RoleMiddleware (PSR-15)
```

## Notas importantes

- **No hay tenant_id** en ninguna tabla. Cada BD es de un solo tenant.
- **No usa `illuminate/events`**: el hasheo de contraseñas se hace vía `setPasswordAttribute()` mutator de Eloquent con `password_hash()` nativo.
- **No usa helpers de Laravel**: usa `new \DateTime()` en vez de `now()`, `password_hash()`/`password_verify()` en vez de `Hash::make()`/`Hash::check()`.
- **Wildcards**: `users.*` coincide con `users.create`, `users.delete`, etc.
- **Roles jerárquicos**: un rol puede tener `parent_id` y hereda permisos del padre.
