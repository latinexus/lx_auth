# LxAuth - Sistema de Autenticación Multi-Tenancy

Un sistema completo de autenticación y autorización multi-tenancy con roles y permisos para PHP.

## 🚀 Características

- ✅ **Multi-Tenancy**: Aislamiento completo de datos por tenant
- ✅ **Roles Jerárquicos**: Sistema de roles con herencia de permisos
- ✅ **Permisos Flexibles**: Soporte para wildcards (`users.*`, `admin:access`)
- ✅ **Autenticación JWT**: Tokens seguros para APIs
- ✅ **Middleware PSR-15**: Protección de rutas estándar
- ✅ **Throttling**: Protección contra fuerza bruta
- ✅ **Caching**: Mejora de rendimiento para roles y permisos
- ✅ **Drivers Extensibles**: Soporte para múltiples bases de datos
- ✅ **Contratos e Interfaces**: Testing fácil y flexibilidad

## 📋 Requisitos

- PHP >= 8.3
- Extensiones: ext-json, ext-openssl, ext-pdo
- Composer

## 🛠️ Instalación

```bash
composer install
```

## 🗄️ Base de Datos

LxAuth soporta múltiples bases de datos. Para MySQL/MariaDB:

### Configuración MySQL/MariaDB

1. **Crear base de datos y usuario**:
```bash
mysql -u root -p < setup_mysql.sql
```

2. **Configurar variables de entorno**:
```bash
cp .env.example .env
# Editar .env con tus credenciales MySQL/MariaDB
```

3. **Ejecutar migraciones**:
```bash
php migrate_mysql.php              # Crear todas las tablas
php migrate_mysql.php --fresh      # Eliminar y recrear todo
php migrate_mysql.php --rollback   # Revertir migraciones
```

4. **Probar la configuración**:
```bash
php test_mysql.php
```

### Variables de Entorno (.env)

```env
# MySQL/MariaDB
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lx_auth
DB_USERNAME=lx_auth_user
DB_PASSWORD=lx_auth_password

# LxAuth
LX_AUTH_JWT_SECRET=your-super-secret-jwt-key-change-in-production
LX_AUTH_LOG_CHANNEL=stack
```

### Estructura de Tablas

- `tenants` - Información de tenants (UUID primary key)
- `users` - Usuarios multi-tenancy
- `roles` - Roles jerárquicos
- `permissions` - Permisos con wildcards
- `role_user` - Relación usuarios-roles
- `permission_role` - Relación roles-permisos
- `permission_user` - Permisos directos a usuarios
- `persistences` - Sesiones persistentes

### Optimizaciones MySQL/MariaDB

- **utf8mb4** para soporte completo de Unicode
- **InnoDB** con foreign keys y CASCADE
- **Índices optimizados** para rendimiento
- **UUID** para tenants (distribución global)
- **IPv6 compatible** en persistences

## 🔧 Configuración Básica

```php
use LxAuth\LxAuth;
use Illuminate\Database\Capsule\Manager as Capsule;

// Configurar base de datos MySQL/MariaDB
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_DATABASE'] ?? 'lx_auth',
    'username' => $_ENV['DB_USERNAME'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'engine' => 'InnoDB',
]);

// Establecer driver para LxAuth
LxAuth\Drivers\Database\EloquentDriver::setCapsule($capsule);

// Inicializar LxAuth
$auth = LxAuth::getInstance([
    'auth' => [
        'password_hash' => 'bcrypt',
        'throttling' => [
            'enabled' => true,
            'max_attempts' => 5,
            'lockout_time' => 300,
        ],
    ],
    'tenancy' => [
        'resolver' => 'subdomain',
    ],
    'roles' => [
        'hierarchical' => true,
        'cache_enabled' => true,
    ],
    'permissions' => [
        'wildcard_enabled' => true,
        'cache_enabled' => true,
    ],
]);
```

## 📚 Uso Básico

### Autenticación

```php
// Autenticar usuario
$user = $auth->authenticate([
    'email' => 'admin@example.com',
    'password' => 'password123'
], 'tenant-1');

// Registrar nuevo usuario
$user = $auth->register([
    'email' => 'newuser@example.com',
    'password' => 'password123',
    'first_name' => 'John',
    'last_name' => 'Doe'
], 'tenant-1');

// Crear token JWT
$token = $auth->createToken($user, 'api');

// Validar token
$user = $auth->validateToken($token);
```

### Roles y Permisos

```php
// Asignar rol
$auth->assignRole($user, 'admin');

// Verificar rol
if ($auth->hasRole('admin')) {
    // Es administrador
}

// Otorgar permiso directo
$auth->givePermissionTo($user, 'users.create');

// Verificar permiso
if ($auth->can('users.create')) {
    // Puede crear usuarios
}

// Permisos con wildcards
if ($auth->can('users.*')) {
    // Puede hacer cualquier operación con usuarios
}
```

### Multi-Tenancy

```php
// Establecer tenant actual
$auth->setTenant('tenant-1');

// Obtener tenant actual
$tenantId = $auth->tenant();

// Resolver tenant automáticamente (subdominio, header, etc.)
$tenantId = $auth->getTenantResolver()->resolve();
```

## 🛡️ Middleware

```php
// Middleware de autenticación
$authMiddleware = $auth->middleware(['/login', '/register']);

// Middleware de permisos
$permissionMiddleware = $auth->permissionMiddleware('users.create');

// Middleware de roles
$roleMiddleware = $auth->roleMiddleware('admin');

// Middleware de tenant
$tenantMiddleware = $auth->tenantMiddleware();
```

## 🧪 Testing

Ejecuta las pruebas para verificar que todo funciona:

```bash
# Tests básicos (SQLite en memoria)
php test_simple.php              # Test básico del modelo
php test_final.php               # Test de compatibilidad completa
php test_migrations.php          # Test de migraciones con datos

# Tests específicos para MySQL/MariaDB
php test_mysql.php               # Test completo con MySQL/MariaDB

# Migraciones
php migrate.php                  # Migraciones SQLite (desarrollo)
php migrate_mysql.php            # Migraciones MySQL/MariaDB (producción)
```

## 📁 Estructura del Proyecto

```
src/
├── LxAuth.php              # Clase principal
├── Models/                 # Modelos Eloquent
│   ├── User.php
│   ├── Role.php
│   ├── Permission.php
│   └── Tenant.php
├── Services/               # Servicios del sistema
│   ├── AuthManager.php
│   ├── RoleManager.php
│   ├── PermissionManager.php
│   └── TenantResolver.php
├── Middleware/             # Middleware PSR-15
├── Drivers/               # Drivers de base de datos
└── Contracts/             # Interfaces y contratos

config/
└── lx_auth.php            # Configuración

database/
└── migrations/            # Migraciones de base de datos

tests/                     # Tests unitarios
```

## 🔑 Conceptos Clave

### Multi-Tenancy
Cada tenant tiene sus propios usuarios, roles y permisos completamente aislados.

### Roles Jerárquicos
Los roles pueden tener roles padre, heredando automáticamente los permisos del padre.

### Permisos con Wildcards
Soporta patrones como:
- `users.create` - Crear usuarios
- `users.*` - Cualquier operación con usuarios
- `*` - Acceso total

### Drivers Extensibles
El sistema soporta diferentes drivers de base de datos. Actualmente incluye:
- **EloquentDriver**: Para Laravel Eloquent
- Fácil extensión para Redis, MongoDB, etc.

## 🚨 Notas Importantes

1. **Producción**: Remueve cualquier código de debug antes de deployar
2. **Secret Keys**: Cambia las claves JWT en producción
3. **Base de Datos**: Configura una base de datos persistente para producción
4. **Caching**: Considera Redis o Memcached para caching en producción

---

## 🔐 Notas sobre JWT (importante)

LxAuth ahora usa `firebase/php-jwt` en su serie 7.x. Esta versión incluye validaciones de seguridad adicionales que pueden afectar a tokens existentes si la configuración no cumple los requisitos mínimos.

- Requisito de longitud del secret (HMAC):
  - `HS256` requiere al menos 256 bits (32 bytes)
  - `HS384` requiere al menos 384 bits (48 bytes)
  - `HS512` requiere al menos 512 bits (64 bytes)

- Si tu `LX_AUTH_JWT_SECRET` es más corto que el mínimo requerido para el algoritmo configurado, la generación de tokens fallará con una excepción. Para evitarlo, asegúrate de usar secrets suficientemente largos.

- Comando recomendado para generar un secret seguro (32 bytes hex):

```bash
php -r "echo bin2hex(random_bytes(32));"
```

- Sugerencia de despliegue/rotación:
  - Considera soportar temporalmente una clave secundaria (`jwt.secret_secondary`) para permitir una rotación segura sin invalidar sesiones inmediatamente; valida primero con la primaria y, si falla, intenta la secundaria.
  - Registra y renueva tokens firmados con la clave secundaria durante el período de transición.

- Validaciones añadidas en LxAuth:
  - `AuthManager` ahora valida la longitud del secret antes de llamar a la librería y sincroniza `JWT::$leeway` desde la configuración (`tokens.jwt.leeway`). Si el secret es insuficiente, se lanzará una excepción con mensaje claro.

---

## 📄 Licencia

MIT License - Ver archivo LICENSE para detalles.

## 🤝 Contribuciones

¡Contribuciones son bienvenidas! Por favor sigue los estándares de código y testing.

---

**LxAuth** - Autenticación robusta y flexible para aplicaciones PHP modernas.
