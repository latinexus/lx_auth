<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 10/02/26
 * Time: 01:16
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Exceptions;

/**
 * Excepción para errores de roles
 */
class RoleException extends \Exception
{
    protected $code = 400;
    protected $message = 'Role operation failed';

    public function __construct(string $message = '', int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message ?: $this->message, $code ?: $this->code, $previous);
    }
}

