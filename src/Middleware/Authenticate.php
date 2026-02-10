<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:36
 * Proyecto: cp_lx_auth
 */


// src/Middleware/Authenticate.php


namespace LxAuth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LxAuth\LxAuth;
use LxAuth\Exceptions\AuthenticationException;

class Authenticate implements MiddlewareInterface
{
    private LxAuth $auth;
    private array $except = [];

    public function __construct(LxAuth $auth, array $except = [])
    {
        $this->auth = $auth;
        $this->except = $except;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Verificar si la ruta está excluida
        if ($this->inExceptArray($request)) {
            return $handler->handle($request);
        }

        // Intentar autenticar por token JWT
        $authHeader = $request->getHeaderLine('Authorization');

        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $user = $this->auth->validateToken($token);

            if ($user) {
                $request = $request->withAttribute('user', $user);
                return $handler->handle($request);
            }
        }

        // Intentar autenticar por sesión
        $user = $this->auth->sessionUser();
        if ($user) {
            $request = $request->withAttribute('user', $user);
            return $handler->handle($request);
        }

        throw new AuthenticationException('No autenticado');
    }

    private function inExceptArray(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        foreach ($this->except as $except) {
            if ($except === $path) {
                return true;
            }

            if (str_contains($except, '*')) {
                $pattern = preg_quote($except, '/');
                $pattern = str_replace('\*', '.*', $pattern);

                if (preg_match('/^' . $pattern . '$/', $path)) {
                    return true;
                }
            }
        }

        return false;
    }
}




