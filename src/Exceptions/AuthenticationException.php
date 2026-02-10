<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:15
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Exceptions;

/**
 * Excepción para errores de autenticación
 */
class AuthenticationException extends \Exception
{
    protected $code = 401;
    protected $message = 'Authentication failed';

    public function __construct(string $message = '', int $code = 401, \Throwable $previous = null)
    {
        parent::__construct($message ?: $this->message, $code ?: $this->code, $previous);
    }
}