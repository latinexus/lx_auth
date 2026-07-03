<?php
/**
 * Migraciones para LxAuth
 * 
 * Uso:
 * php migrate.php              # Ejecutar todas las migraciones
 * php migrate.php --rollback   # Revertir todas las migraciones
 * php migrate.php --fresh      # Eliminar y recrear todo
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class Migrator
{
    private Capsule $capsule;
    private array $migrations;
    private string $dbPath;

    public function __construct()
    {
        $this->dbPath = ':memory:'; // Usar base de datos en memoria para evitar problemas de permisos
        $this->setupCapsule();
        $this->loadMigrations();
    }

    private function setupCapsule(): void
    {
        // Para base de datos en memoria, no necesitamos crear directorios ni archivos
        
        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => $this->dbPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }

    private function loadMigrations(): void
    {
        $this->migrations = [
            '0002_create_users_table',
            '0003_create_roles_table',
            '0004_create_permissions_table',
            '0005_create_role_user_table',
            '0006_create_permission_role_table',
            '0007_create_permission_user_table',
            '0008_create_persistences_table',
        ];
    }

    public function migrate(): void
    {
        echo "🚀 Ejecutando migraciones de LxAuth...\n\n";

        foreach ($this->migrations as $migration) {
            echo "📝 Ejecutando: {$migration}...\n";
            
            $method = 'create' . $this->getTableName($migration);
            if (method_exists($this, $method)) {
                $this->$method();
                echo "✅ {$migration} completado\n";
            } else {
                echo "❌ Método {$method} no encontrado\n";
            }
        }

        echo "\n🎉 ¡Todas las migraciones completadas!\n";
        echo "📁 Base de datos creada en: {$this->dbPath}\n";
    }

    public function rollback(): void
    {
        echo "🔄 Revirtiendo migraciones de LxAuth...\n\n";

        // Para base de datos en memoria, no hay nada que revertir
        if ($this->dbPath === ':memory:') {
            echo "⚠️  Base de datos en memoria: no hay nada que revertir.\n";
            return;
        }

        // Si la base de datos no existe, no hay nada que revertir
        if (!file_exists($this->dbPath)) {
            echo "⚠️  La base de datos no existe. No hay nada que revertir.\n";
            return;
        }

        $reverseMigrations = array_reverse($this->migrations);
        
        foreach ($reverseMigrations as $migration) {
            echo "🗑️  Revirtiendo: {$migration}...\n";
            
            $tableName = $this->getTableName($migration);
            try {
                if ($this->capsule->schema()->hasTable($tableName)) {
                    $this->capsule->schema()->dropIfExists($tableName);
                    echo "✅ Tabla {$tableName} eliminada\n";
                } else {
                    echo "⚠️  Tabla {$tableName} no existe\n";
                }
            } catch (\Exception $e) {
                echo "❌ Error eliminando tabla {$tableName}: " . $e->getMessage() . "\n";
            }
        }

        // Eliminar el archivo de base de datos si está vacío
        if (file_exists($this->dbPath)) {
            try {
                unlink($this->dbPath);
                echo "🗑️  Archivo de base de datos eliminado\n";
            } catch (\Exception $e) {
                echo "⚠️  No se pudo eliminar el archivo de base de datos\n";
            }
        }

        echo "\n🎉 ¡Migraciones revertidas!\n";
    }

    public function fresh(): void
    {
        echo "🔥 Eliminando y recreando base de datos...\n";
        $this->rollback();
        echo "\n";
        $this->migrate();
    }

    private function getTableName(string $migration): string
    {
        // Extraer el nombre de la tabla del nombre de la migración
        if (preg_match('/create_(.+)_table/', $migration, $matches)) {
            // Convertir snake_case a PascalCase
            // ej: 'role_user' → 'RoleUser'
            $name = str_replace('_', ' ', $matches[1]);
            $name = ucwords($name);
            return str_replace(' ', '', $name);
        }
        return 'unknown';
    }

    // ========== MÉTODOS DE MIGRACIÓN ==========

    private function createUsers(): void
    {
        $this->capsule->schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login')->nullable();
            $table->json('permissions')->nullable();
            $table->json('meta')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['email', 'is_active']);
        });
    }

    private function createRoles(): void
    {
        $this->capsule->schema()->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->index('slug');
            $table->index('level');
            $table->foreign('parent_id')->references('id')->on('roles')->onDelete('set null');
        });
    }

    private function createPermissions(): void
    {
        $this->capsule->schema()->create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_wildcard')->default(false);
            $table->timestamps();
            $table->index('slug');
            $table->index('is_wildcard');
        });
    }

    private function createRoleUser(): void
    {
        $this->capsule->schema()->create('role_user', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->primary(['role_id', 'user_id']);
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    private function createPermissionRole(): void
    {
        $this->capsule->schema()->create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            $table->primary(['permission_id', 'role_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    private function createPermissionUser(): void
    {
        $this->capsule->schema()->create('permission_user', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('grant')->default(true);
            $table->timestamps();

            $table->primary(['permission_id', 'user_id']);
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    private function createPersistences(): void
    {
        $this->capsule->schema()->create('persistences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token')->unique();
            $table->timestamp('expires_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'token']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
}

// ========== EJECUCIÓN ==========

$command = $argv[1] ?? 'migrate';

$migrator = new Migrator();

switch ($command) {
    case '--rollback':
        $migrator->rollback();
        break;
    case '--fresh':
        $migrator->fresh();
        break;
    case 'migrate':
    default:
        $migrator->migrate();
        break;
}
