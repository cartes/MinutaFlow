<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use BelongsToTenant, HasFactory, HasUuids, SoftDeletes;

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'company_id',
        'name',
        'address',
        'commune',
        'region',
        'contact_name',
        'contact_phone',
        'delivery_time_start',
        'delivery_time_end',
        'delivery_notes',
        'latitude',
        'longitude',
        'is_active',
    ];

    /**
     * Conversiones de tipos (Casting) para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'is_active' => 'boolean',
        ];
    }

    /* =========================================================================
     * RELACIONES ELOQUENT
     * ========================================================================= */

    /**
     * Empresa de catering (Tenant) responsable del despacho.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Empresa corporativa a la que pertenece esta sucursal/sede.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Empleados que tienen asignada esta sucursal como punto de retiro habitual.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Pedidos programados para ser despachados a esta sucursal.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
