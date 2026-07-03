<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 03/07/26
 * Proyecto: cp_lx_auth
 */

namespace LxAuth\Exceptions;

/**
 * Excepción para errores de operaciones con permisos
 * (ej: intentar modificar un permiso del sistema)
 */
class PermissionException extends \Exception
{
    protected $code = 400;
    protected $message = 'Permission operation failed';

    public function __construct(string $message = '', int $code = 400, \Throwable $previous = null)
    {
        parent::__construct($message ?: $this->message, $code ?: $this->code, $previous);
    }
}
