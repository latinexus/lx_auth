<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:37
 * Proyecto: cp_lx_auth
 */



namespace LxAuth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LxAuth\LxAuth;
use LxAuth\Exceptions\PermissionDeniedException;

class RoleMiddleware implements MiddlewareInterface
{
    private LxAuth $auth;
    private string $role;

    public function __construct(LxAuth $auth, string $role)
    {
        $this->auth = $auth;
        $this->role = $role;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            throw new PermissionDeniedException('Usuario no autenticado');
        }

        if (!$this->auth->hasRole($this->role)) {
            throw new PermissionDeniedException("Requiere el rol: {$this->role}");
        }

        return $handler->handle($request);
    }
}

