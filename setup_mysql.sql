-- Script SQL para crear base de datos y usuario para LxAuth
-- Compatible con MySQL 5.7+ y MariaDB 10.2+

-- Crear base de datos
CREATE DATABASE IF NOT EXISTS lx_auth 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Crear usuario (ajustar contraseña según necesites)
CREATE USER IF NOT EXISTS 'lx_auth_user'@'localhost' 
IDENTIFIED BY 'lx_auth_password';

-- Otorgar permisos
GRANT ALL PRIVILEGES ON lx_auth.* TO 'lx_auth_user'@'localhost';

-- Para acceso remoto (opcional)
-- CREATE USER IF NOT EXISTS 'lx_auth_user'@'%' 
-- IDENTIFIED BY 'lx_auth_password';
-- GRANT ALL PRIVILEGES ON lx_auth.* TO 'lx_auth_user'@'%';

-- Aplicar cambios
FLUSH PRIVILEGES;

-- Usar la base de datos
USE lx_auth;

-- Mostrar información
SELECT 'Database lx_auth created successfully' as status;
SELECT 'User lx_auth_user created successfully' as status;
