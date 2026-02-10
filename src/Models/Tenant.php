<?php
/**
 * Creator: Eric Larrea
 * E-mail: eric@latinex.us
 * From: www.latinex.us
 * Date: 08/02/26
 * Time: 15:29
 * Proyecto: cp_lx_auth
 */


namespace LxAuth\Models;

use Illuminate\Database\Eloquent\Model;
use LxAuth\Contracts\TenantInterface;

class Tenant extends Model implements TenantInterface
{
    protected $table = 'tenants';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'domain',
        'config',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array'
    ];

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getConfig(): array
    {
        return $this->config ?? [];
    }

    public function isActive(): bool
    {
        return (bool)$this->is_active;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updated_at;
    }

    public function users()
    {
        return $this->hasMany(User::class, 'tenant_id', 'id');
    }
}