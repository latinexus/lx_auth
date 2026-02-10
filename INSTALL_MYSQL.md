# Guía Rápida de Instalación para MySQL/MariaDB

## 1. Prerrequisitos

- PHP >= 8.3
- MySQL 5.7+ o MariaDB 10.2+
- Composer
- Extensiones PHP: ext-json, ext-openssl, ext-pdo, ext-mysqlnd

## 2. Instalación

```bash
# Clonar o descargar el proyecto
cd lx_auth

# Instalar dependencias
composer install
```

## 3. Configuración de Base de Datos

### Opción A: Usar script SQL (recomendado)

```bash
# Crear base de datos y usuario automáticamente
mysql -u root -p < setup_mysql.sql
```

### Opción B: Configuración manual

```sql
-- Conéctate a MySQL/MariaDB como root
mysql -u root -p

-- Ejuta estos comandos
CREATE DATABASE lx_auth CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'lx_auth_user'@'localhost' IDENTIFIED BY 'lx_auth_password';
GRANT ALL PRIVILEGES ON lx_auth.* TO 'lx_auth_user'@'localhost';
FLUSH PRIVILEGES;
```

## 4. Configurar Variables de Entorno

```bash
# Copiar archivo de ejemplo
cp .env.example .env

# Editar .env con tus credenciales
nano .env
```

Ajusta estos valores en `.env`:
```env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lx_auth
DB_USERNAME=lx_auth_user
DB_PASSWORD=lx_auth_password
```

## 5. Ejecutar Migraciones

```bash
# Crear todas las tablas
php migrate_mysql.php

# Verificar que todo funcionó
php test_mysql.php
```

## 6. Verificación Final

Deberías ver una salida similar a:
```
🧪 Probando LxAuth con MySQL/MariaDB...

✅ Conexión a MySQL/MariaDB establecida
✅ LxAuth inicializado

📝 Creando datos de prueba...
✅ Tenant creado: Test Tenant MySQL
✅ Usuario creado: admin@mysql.test
✅ Rol creado: Administrator
✅ Permiso creado: Manage Users
✅ Relaciones establecidas

🔐 Probando funcionalidad de LxAuth...
✅ Autenticación exitosa: admin@mysql.test
✅ Verificación de rol: admin
✅ Verificación de permiso: users.manage
✅ Token JWT creado
✅ Token JWT validado correctamente

🎉 ¡PRUEBAS COMPLETADAS EXITOSAMENTE!
```

## 7. ¡Listo para Usar!

El sistema está completamente configurado y listo para producción. Puedes:

- Integrar LxAuth en tu aplicación
- Usar los middleware para proteger rutas
- Implementar autenticación JWT
- Gestionar roles y permisos multi-tenancy

## Troubleshooting

### Error de conexión
```bash
# Verifica que MySQL/MariaDB esté corriendo
systemctl status mysql  # o systemctl status mariadb

# Verifica credenciales
mysql -u lx_auth_user -p lx_auth
```

### Error de permisos
```bash
# Asegúrate de que el usuario tenga los permisos correctos
mysql -u root -p
GRANT ALL PRIVILEGES ON lx_auth.* TO 'lx_auth_user'@'localhost';
FLUSH PRIVILEGES;
```

### Error de extensiones PHP
```bash
# Instala extensiones faltantes
sudo apt-get install php-mysql php-json php-openssl  # Ubuntu/Debian
sudo yum install php-mysqlnd php-json php-openssl     # CentOS/RHEL
```
