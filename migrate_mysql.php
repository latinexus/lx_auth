<?php
/**
 * Migraciones para LxAuth - MySQL/MariaDB
 * 
 * Uso:
 * php migrate_mysql.php              # Ejecutar todas las migraciones
 * php migrate_mysql.php --rollback   # Revertir todas las migraciones
 * php migrate_mysql.php --fresh      # Eliminar y recrear todo
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class MySQLMigrator
{
    private Capsule $capsule;
    private array $migrations;

    public function __construct()
    {
        $this->setupCapsule();
        $this->loadMigrations();
    }

    private function setupCapsule(): void
    {
        $this->capsule = new Capsule;
        $this->capsule->addConnection([
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'lx_auth',
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
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
        echo "🚀 Ejecutando migraciones de LxAuth para MySQL/MariaDB...\n\n";

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
    }

    public function rollback(): void
    {
        echo "🔄 Revirtiendo migraciones de LxAuth...\n\n";

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
        if (preg_match('/create_(.+)_table/', $migration, $matches)) {
            // Convertir snake_case a PascalCase
            // ej: 'role_user' → 'RoleUser'
            $name = str_replace('_', ' ', $matches[1]);
            $name = ucwords($name);
            return str_replace(' ', '', $name);
        }
        return 'unknown';
    }

    // ========== MÉTODOS DE MIGRACIÓN PARA MYSQL/MARIADB ==========

    private function createUsers(): void
    {
        $this->capsule->schema()->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email', 191)->unique();
            $table->string('password', 255);
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
            $table->index('last_login');
        });
    }

    private function createRoles(): void
    {
        $this->capsule->schema()->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('level')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->index('slug');
            $table->index('level');
            $table->index(['parent_id']);
            $table->foreign('parent_id')
                  ->references('id')->on('roles')
                  ->onDelete('set null');
        });
    }

    private function createPermissions(): void
    {
        $this->capsule->schema()->create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 150)->unique();
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

            // Primary key compuesta
            $table->primary(['role_id', 'user_id']);
            
            // Foreign keys con CASCADE
            $table->foreign('role_id')
                  ->references('id')->on('roles')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    private function createPermissionRole(): void
    {
        $this->capsule->schema()->create('permission_role', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();

            // Primary key compuesta
            $table->primary(['permission_id', 'role_id']);
            
            // Foreign keys con CASCADE
            $table->foreign('permission_id')
                  ->references('id')->on('permissions')
                  ->onDelete('cascade');
                  
            $table->foreign('role_id')
                  ->references('id')->on('roles')
                  ->onDelete('cascade');
        });
    }

    private function createPermissionUser(): void
    {
        $this->capsule->schema()->create('permission_user', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('grant')->default(true);
            $table->timestamps();

            // Primary key compuesta
            $table->primary(['permission_id', 'user_id']);
            
            // Foreign keys con CASCADE
            $table->foreign('permission_id')
                  ->references('id')->on('permissions')
                  ->onDelete('cascade');
                  
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    private function createPersistences(): void
    {
        $this->capsule->schema()->create('persistences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('token', 255)->unique();
            $table->timestamp('expires_at')->nullable();
            $table->string('ip_address', 45)->nullable(); // IPv6 compatible
            $table->string('user_agent')->nullable();
            $table->timestamps();

            // Índices para rendimiento
            $table->index(['user_id', 'token']);
            $table->index('expires_at');
            
            // Foreign key con CASCADE
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }
}

// ========== EJECUCIÓN ==========

$command = $argv[1] ?? 'migrate';

$migrator = new MySQLMigrator();

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
