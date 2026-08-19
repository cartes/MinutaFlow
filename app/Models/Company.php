<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use BelongsToTenant, HasFactory, HasUuids, SoftDeletes;

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'rut',
        'billing_email',
        'billing_address',
        'contact_name',
        'contact_phone',
        'cutoff_time',
        'cutoff_days_in_advance',
        'subsidy_percentage',
        'is_active',
        'settings',
    ];

    /**
     * Conversiones de tipos (Casting) para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'cutoff_days_in_advance' => 'integer',
            'subsidy_percentage' => 'decimal:2',
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /* =========================================================================
     * RELACIONES ELOQUENT
     * ========================================================================= */

    /**
     * Empresa de catering (Tenant) que provee el servicio.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Sucursales o sedes de entrega pertenecientes a esta empresa cliente.
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Empleados y administradores de RRHH pertenecientes a esta empresa.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Menús personalizados o exclusivos creados para esta empresa.
     */
    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * Historial de pedidos realizados por los empleados de esta empresa.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
