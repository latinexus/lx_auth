<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:04
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Contracts;



/**
 * Interfaz para usuarios del sistema
 */
interface UserInterface
{
    /**
     * Obtiene el ID único del usuario
     */
    public function getId(): int|string;

    /**
     * Obtiene el ID del tenant
     */
    public function getTenantId(): ?string;

    /**
     * Obtiene el email del usuario
     */
    public function getEmail(): string;

    /**
     * Verifica si el usuario está activo
     */
    public function isActive(): bool;

    /**
     * Obtiene los roles del usuario
     *
     * @return array<RoleInterface>
     */
    public function getRoles(): array;

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole(string $role): bool;

    /**
     * Obtiene los permisos directos del usuario
     */
    public function getDirectPermissions(): array;

    /**
     * Obtiene la fecha de último login
     */
    public function getLastLogin(): ?\DateTimeInterface;

    /**
     * Actualiza la fecha de último login
     */
    public function updateLastLogin(): void;

    /**
     * Obtiene el hash de la contraseña
     */
    public function getPasswordHash(): string;

    /**
     * Verifica una contraseña
     */
    public function verifyPassword(string $password): bool;

    /**
     * Obtiene atributos adicionales
     */
    public function getAttributes(): array;

    /**
     * Obtiene un atributo específico
     *
     * Obtener un atributo del usuario
     * Nota: No podemos llamarlo get Attribute() por conflicto con Eloquent
     */
    public function getUserAttribute(string $key, $default = null);

    /**
     * Método seguro para obtener atributos sin recursión
     */
    public function getCustomAttribute(string $key, $default = null);
}