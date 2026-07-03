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

class PermissionMiddleware implements MiddlewareInterface
{
    private LxAuth $auth;
    private string $permission;
    private ?array $context;

    public function __construct(LxAuth $auth, string $permission, ?array $context = null)
    {
        $this->auth = $auth;
        $this->permission = $permission;
        $this->context = $context;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $request->getAttribute('user');

        if (!$user) {
            throw new PermissionDeniedException('Usuario no autenticado');
        }

        if (!$this->auth->can($this->permission, $this->context)) {
            throw new PermissionDeniedException("Permiso denegado: {$this->permission}");
        }

        return $handler->handle($request);
    }
}

