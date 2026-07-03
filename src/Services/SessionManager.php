<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 03/07/26
 * Proyecto: cp_lx_auth
 */

namespace LxAuth\Services;

use LxAuth\Contracts\UserInterface;
use LxAuth\Drivers\Database\DatabaseDriverInterface;

class SessionManager
{
    private DatabaseDriverInterface $driver;
    private array $config;
    private bool $sessionStarted = false;

    public function __construct(DatabaseDriverInterface $driver, array $config)
    {
        $this->driver = $driver;
        $this->config = $config['session'] ?? [];
    }

    public function login(UserInterface $user, bool $remember = false): void
    {
        $driver = $this->config['driver'] ?? 'native';

        if ($driver !== 'native') {
            return;
        }

        $this->start();

        $_SESSION['lx_auth'] = [
            'user_id' => $user->getId(),
            'email' => $user->getEmail(),
            'logged_in_at' => time(),
            'remember' => $remember,
        ];

        if ($remember) {
            $lifetime = $this->config['remember_lifetime'] ?? 2592000;
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                session_id(),
                time() + $lifetime,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_regenerate_id(true);
    }

    public function logout(): void
    {
        if (!$this->sessionStarted) {
            return;
        }

        unset($_SESSION['lx_auth']);
        session_destroy();
        $this->sessionStarted = false;

        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    public function getUser(): ?UserInterface
    {
        $driver = $this->config['driver'] ?? 'native';

        if ($driver !== 'native') {
            return null;
        }

        $this->start();

        if (!isset($_SESSION['lx_auth'])) {
            return null;
        }

        $authData = $_SESSION['lx_auth'];
        $sessionLifetime = ($this->config['lifetime'] ?? 120) * 60;

        if ((time() - $authData['logged_in_at']) > $sessionLifetime) {
            if (!($authData['remember'] ?? false)) {
                $this->logout();
                return null;
            }
        }

        if (($authData['remember'] ?? false)) {
            $rememberLifetime = $this->config['remember_lifetime'] ?? 2592000;
            if ((time() - $authData['logged_in_at']) > $rememberLifetime) {
                $this->logout();
                return null;
            }
        }

        return $this->driver->findUserById($authData['user_id']);
    }

    public function hasUser(): bool
    {
        return $this->getUser() !== null;
    }

    private function start(): void
    {
        if (!$this->sessionStarted && session_status() === PHP_SESSION_NONE) {
            $sessionName = $this->config['name'] ?? session_name();
            if ($sessionName) {
                session_name($sessionName);
            }

            $cookieParams = [
                'lifetime' => ($this->config['lifetime'] ?? 120) * 60,
                'path' => '/',
                'domain' => '',
                'secure' => $this->config['secure'] ?? false,
                'httponly' => $this->config['http_only'] ?? true,
                'samesite' => $this->config['same_site'] ?? 'Lax',
            ];

            session_set_cookie_params($cookieParams);

            session_start();
            $this->sessionStarted = true;
        }
    }
}
