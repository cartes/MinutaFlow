<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string|null $company_id
 * @property string $title
 * @property \Illuminate\Support\Carbon $menu_date
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Tenant $tenant
 * @property-read \App\Models\Company|null $company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\MenuItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 */
class Menu extends Model
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
        'title',
        'menu_date',
        'is_published',
    ];

    /**
     * Conversiones de tipos (Casting) para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'menu_date' => 'date',         // Instancia Carbon de la fecha del servicio
            'is_published' => 'boolean',   // Indica si los comensales ya pueden ver y pedir este menú
        ];
    }

    /* =========================================================================
     * RELACIONES ELOQUENT
     * ========================================================================= */

    /**
     * Empresa de catering (Tenant) que elabora el menú.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Empresa cliente específica si el menú es personalizado/exclusivo para ella.
     * Si es null, representa un menú general/común para todos los clientes del catering.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Opciones de platos disponibles dentro de este menú diario (ej: Opción 1, Opción 2, Opción Vegana).
     */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /**
     * Pedidos realizados por los comensales asociados a este menú.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* =========================================================================
     * LOCAL SCOPES (Consultas predefinidas)
     * ========================================================================= */

    /**
     * Scope para obtener sólo menús publicados visibles para comensales.
     * Uso: Menu::published()->get();
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Scope para filtrar el menú de una fecha específica.
     * Uso: Menu::forDate('2026-08-20')->get();
     */
    public function scopeForDate(Builder $query, string|CarbonInterface $date): Builder
    {
        return $query->whereDate('menu_date', $date);
    }

    /**
     * Scope para obtener menús generales (no exclusivos de una empresa cliente).
     * Uso: Menu::general()->get();
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->whereNull('company_id');
    }

    /* =========================================================================
     * HELPERS / MÉTODOS DE NEGOCIO
     */

    /**
     * Determina si el menú es exclusivo para una empresa cliente.
     */
    public function isCustomForCompany(): bool
    {
        return $this->company_id !== null;
    }
}
