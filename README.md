# LxAuth - Sistema de Autenticación y Autorización

Librería PHP para autenticación, roles y permisos. Trabaja contra **una única base de datos por instancia**. Ideal para arquitecturas SaaS donde cada cliente (tenant) tiene su propia base de datos.

> **Nota**: La resolución del tenant (cliente) y sus credenciales de conexión son **responsabilidad de la aplicación** que consume LX_AUTH. LX_AUTH recibe una conexión ya configurada y opera exclusivamente contra ella.

## 🚀 Características

- ✅ **Roles Jerárquicos**: Sistema de roles con herencia de permisos
- ✅ **Permisos Flexibles**: Soporte para wildcards (`users.*`, `admin:access`)
- ✅ **Autenticación JWT**: Tokens seguros para APIs
- ✅ **Middleware PSR-15**: Protección de rutas estándar
- ✅ **Throttling**: Protección contra fuerza bruta
- ✅ **Caching**: Mejora de rendimiento para roles y permisos
- ✅ **Sesiones PHP**: Gestión nativa de sesiones con "remember me"
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

## 🗄️ Esquema de Base de Datos

Cada base de datos donde opere LX_AUTH debe tener las siguientes tablas:

| Tabla | Descripción |
|---|---|
| `users` | Usuarios del sistema |
| `roles` | Roles jerárquicos |
| `permissions` | Permisos con wildcards |
| `role_user` | Relación usuarios-roles |
| `permission_role` | Relación roles-permisos |
| `permission_user` | Permisos directos a usuarios |
| `persistences` | Sesiones persistentes |

### Migraciones

```bash
php migrate.php                    # SQLite (desarrollo/pruebas)
php migrate_mysql.php              # MySQL/MariaDB
php migrate_mysql.php --fresh      # Recrear tablas
php migrate_mysql.php --rollback   # Revertir
```

## 🔧 Configuración Básica

```php
use LxAuth\LxAuth;
use LxAuth\Drivers\Database\EloquentDriver;
use Illuminate\Database\Capsule\Manager as Capsule;

// 1. Tu app resuelve el tenant y configura la conexión a su BD
$capsule = new Capsule;
$capsule->addConnection([
    'driver'   => 'mysql',
    'host'     => '...',
    'database' => E_DB_NAM,    // 'cargo_prueba'
    'username' => E_DB_USR,    // 'cargo_prueba_usr'
    'password' => E_DB_PWD,
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

// 2. Pasar la instancia de Capsule a LX_AUTH
EloquentDriver::setCapsule($capsule);

// 3. Inicializar LX_AUTH
$auth = LxAuth::getInstance([
    'tokens' => [
        'jwt' => [
            'secret' => 'tu-clave-secreta',
        ],
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
]);

// Registrar nuevo usuario
$user = $auth->register([
    'email' => 'newuser@example.com',
    'password' => 'password123',
    'first_name' => 'John',
    'last_name' => 'Doe'
]);

// Iniciar sesión (PHP session)
$auth->login($user, remember: true);

// Verificar sesión activa
if ($auth->check()) {
    $user = $auth->user();
}

// Cerrar sesión
$auth->logout();

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

### Middleware PSR-15

```php
// Proteger rutas
$app->add($auth->middleware());                          // Auth requerido
$app->add($auth->permissionMiddleware('users.create'));  // Permiso específico
$app->add($auth->roleMiddleware('admin'));               // Rol específico
```
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
