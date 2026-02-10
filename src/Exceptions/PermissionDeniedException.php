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
 * Excepción para errores de permisos
 */
class PermissionDeniedException extends \Exception
{
    protected $code = 403;
    protected $message = 'Permission denied';

    public function __construct(string $message = '', int $code = 403, \Throwable $previous = null)
    {
        parent::__construct($message ?: $this->message, $code ?: $this->code, $previous);
    }
}