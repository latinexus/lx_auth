<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:38
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use LxAuth\Services\TenantResolver;

class TenantMiddleware implements MiddlewareInterface
{
    private TenantResolver $resolver;

    public function __construct(TenantResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $tenantId = $this->resolver->resolve();
        $request = $request->withAttribute('tenant', $tenantId);
        return $handler->handle($request);
    }
}

