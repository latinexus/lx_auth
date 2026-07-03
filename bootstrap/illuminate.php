<?php
/**
 * Inicializador mínimo para el Container de Illuminate y Facades.
 * LX_AUTH usa password_hash() nativo de PHP, no requiere el servicio Hash.
 *
 * Uso:
 *   $init = require __DIR__ . '/bootstrap/illuminate.php';
 *   $app = $init($capsule ?? null);
 */

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use Illuminate\Database\Capsule\Manager as Capsule;

if (defined('LX_ILLUMINATE_BOOTSTRAPPED') && LX_ILLUMINATE_BOOTSTRAPPED === true) {
    return function (?Capsule $c = null) { return Facade::getFacadeApplication(); };
}

if (!defined('LX_ILLUMINATE_BOOTSTRAPPED')) {
    define('LX_ILLUMINATE_BOOTSTRAPPED', true);
}

return function (?Capsule $capsule = null) {
    $app = new Container();

    if ($capsule instanceof Capsule) {
        try {
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
        } catch (\Throwable $_) {
            // Ignorar si ya bootstrapped
        }

        try {
            $db = $capsule->getDatabaseManager();
            $app->instance('db', $db);
            $capsule->setEventDispatcher(new Dispatcher($app));
        } catch (\Throwable $_) {
            // ignora
        }
    }

    Facade::setFacadeApplication($app);

    return $app;
};

