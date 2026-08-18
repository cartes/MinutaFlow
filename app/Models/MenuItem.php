<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    use HasFactory, HasUuids;

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'menu_id',
        'dish_id',
        'option_label',
        'max_quota',
        'price_extra_clp',
        'is_available',
        'sort_order',
    ];

    /**
     * Conversiones de tipos (Casting) para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_quota' => 'integer',        // Cupo máximo de platos que cocina puede preparar
            'price_extra_clp' => 'integer',  // Copago extra si es un plato gourmet/premium
            'is_available' => 'boolean',     // Si está habilitado para pedidos
            'sort_order' => 'integer',
        ];
    }

    /* =========================================================================
     * RELACIONES ELOQUENT
     * ========================================================================= */

    /**
     * Menú diario al que pertenece esta opción.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Receta / Plato físico asociado a esta opción.
     */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }

    /**
     * Pedidos que han seleccionado esta opción específica.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* =========================================================================
     * LOCAL SCOPES (Consultas predefinidas)
     * ========================================================================= */

    /**
     * Scope para filtrar solo ítems disponibles.
     * Uso: MenuItem::available()->get();
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    /* =========================================================================
     * MÉTODOS DE NEGOCIO Y CONTROL DE STOCK / CUPOS
     * ========================================================================= */

    /**
     * Obtiene la cantidad de pedidos activos que consumen cupo para esta opción.
     * Considera pedidos confirmados, entregados y no retirados (solo los cancelados liberan cupo).
     */
    public function getConfirmedOrdersCount(): int
    {
        return $this->orders()
            ->whereIn('status', [
                OrderStatus::Confirmed,
                OrderStatus::Delivered,
                OrderStatus::NoShow,
            ])
            ->count();
    }

    /**
     * Determina si la opción ha agotado su cupo límite de stock en cocina.
     */
    public function isSoldOut(): bool
    {
        if (!$this->is_available) {
            return true;
        }

        // Si max_quota es null, no hay límite estricto de cupo
        if ($this->max_quota === null) {
            return false;
        }

        return $this->getConfirmedOrdersCount() >= $this->max_quota;
    }

    /**
     * Retorna los cupos restantes disponibles para esta opción.
     * Retorna null si no tiene un límite definido.
     */
    public function remainingQuota(): ?int
    {
        if ($this->max_quota === null) {
            return null;
        }

        $remaining = $this->max_quota - $this->getConfirmedOrdersCount();
        return max(0, $remaining);
    }
}
