<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Models\Concerns\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property string $id
 * @property string $tenant_id
 * @property string $company_id
 * @property string $branch_id
 * @property string $user_id
 * @property string $menu_id
 * @property string $menu_item_id
 * @property \Illuminate\Support\Carbon $order_date
 * @property \App\Enums\OrderStatus $status
 * @property string|null $qr_code_hash
 * @property int $copay_amount_clp
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $delivered_at
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Tenant $tenant
 * @property-read \App\Models\Company $company
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Menu $menu
 * @property-read \App\Models\MenuItem $menuItem
 */
class Order extends Model
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
        'branch_id',
        'user_id',
        'menu_id',
        'menu_item_id',
        'order_date',
        'status',
        'qr_code_hash',
        'copay_amount_clp',
        'notes',
        'delivered_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    /**
     * Conversiones de tipos (Casting) para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'status' => OrderStatus::class,   // Cast automático a enum OrderStatus
            'copay_amount_clp' => 'integer',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Eventos de ciclo de vida del modelo Eloquent.
     */
    protected static function booted(): void
    {
        // Genera automáticamente un hash único para el QR al momento de crear el pedido
        static::creating(function (Order $order) {
            if (empty($order->qr_code_hash)) {
                $order->qr_code_hash = static::generateUniqueQrHash();
            }
        });
    }

    /* =========================================================================
     * RELACIONES ELOQUENT
     * ========================================================================= */

    /**
     * Empresa de catering (Tenant) que atiende la orden.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Empresa cliente a la que pertenece el comensal.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Sucursal física donde se debe entregar el almuerzo.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Comensal / Empleado que solicitó el pedido.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Menú general del día.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Opción de plato elegida por el comensal.
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /* =========================================================================
     * LOCAL SCOPES (Consultas predefinidas)
     * ========================================================================= */

    /**
     * Filtra los pedidos correspondientes al día de hoy.
     * Uso: Order::today()->get();
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('order_date', now()->toDateString());
    }

    /**
     * Filtra pedidos por fecha de servicio.
     * Uso: Order::forDate('2026-08-20')->get();
     */
    public function scopeForDate(Builder $query, string|CarbonInterface $date): Builder
    {
        return $query->whereDate('order_date', $date);
    }

    /**
     * Filtra pedidos confirmados pendientes de entrega (para packing list de cocina).
     * Uso: Order::pendingDelivery()->get();
     */
    public function scopePendingDelivery(Builder $query): Builder
    {
        return $query->where('status', OrderStatus::Confirmed)
                     ->whereNull('delivered_at');
    }

    /**
     * Filtra pedidos por sucursal específica.
     * Uso: Order::forBranch($branchId)->get();
     */
    public function scopeForBranch(Builder $query, string $branchId): Builder
    {
        return $query->where('branch_id', $branchId);
    }

    /* =========================================================================
     * ACCIONES DE NEGOCIO Y TRANSICIÓN DE ESTADOS
     * ========================================================================= */

    /**
     * Marca el pedido como entregado (cuando el personal escanea el QR en recepción).
     */
    public function markAsDelivered(): void
    {
        $this->update([
            'status' => OrderStatus::Delivered,
            'delivered_at' => now(),
        ]);
    }

    /**
     * Cancela el pedido dentro del plazo permitido.
     *
     * @param string|null $reason Motivo de la cancelación (opcional)
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    /**
     * Marca el pedido como No Retirado (No-Show).
     */
    public function markAsNoShow(): void
    {
        $this->update([
            'status' => OrderStatus::NoShow,
        ]);
    }

    /**
     * Genera un hash aleatorio de 64 caracteres criptográficamente seguro para el QR.
     */
    public static function generateUniqueQrHash(): string
    {
        return hash('sha256', Str::uuid()->toString() . microtime());
    }
}
