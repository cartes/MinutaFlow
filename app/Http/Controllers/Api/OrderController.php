<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\Company;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Lista los pedidos según el rol del usuario:
     * - Employee: solo sus propios pedidos.
     * - CompanyAdmin: pedidos de su empresa.
     * - TenantAdmin / KitchenOperator: todos los pedidos del catering (packing list).
     *
     * GET /api/v1/orders?date=2026-08-18&status=confirmed&branch_id=...
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        $user = $request->user();

        $query = Order::query()
            ->with(['menuItem.dish', 'menu:id,title,menu_date', 'branch:id,name'])
            ->when($request->filled('date'), fn ($q) => $q->forDate($request->string('date')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('branch_id'), fn ($q) => $q->forBranch($request->string('branch_id')))
            ->orderByDesc('order_date');

        if ($user->isEmployee()) {
            $query->where('user_id', $user->id);
        } elseif ($user->isCompanyAdmin()) {
            $query->where('company_id', $user->company_id)
                  ->with('user:id,name,email');
        } else {
            // TenantAdmin / KitchenOperator: vista completa con datos del comensal y empresa
            $query->with(['user:id,name,email', 'company:id,name']);
        }

        return response()->json($query->paginate($request->integer('per_page', 25)));
    }

    /**
     * Crea el pedido de almuerzo de un comensal, aplicando las reglas de negocio:
     * - El menú debe estar publicado y ser visible para la empresa del comensal.
     * - El pedido debe realizarse antes del horario de corte de la empresa.
     * - La opción debe tener cupo disponible (verificado con lock para evitar sobreventa).
     * - Un comensal solo puede tener un pedido activo por día (si tiene uno cancelado, se reactiva).
     * - Si el plato contiene un alérgeno del comensal, se exige confirmación explícita.
     *
     * POST /api/v1/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $this->authorize('create', Order::class);

        $user = $request->user();

        abort_unless(
            $user->company_id !== null && $user->branch_id !== null,
            403,
            'Tu cuenta no tiene empresa o sucursal asignada. Contacta a tu administrador.'
        );

        $data = $request->validated();

        /** @var MenuItem $item */
        $item = MenuItem::with(['menu', 'dish'])->findOrFail($data['menu_item_id']);
        $menu = $item->menu;

        abort_unless($menu !== null, 404);
        abort_unless($menu->is_published, 422, 'Este menú aún no está disponible para pedidos.');
        abort_unless(
            $menu->company_id === null || $menu->company_id === $user->company_id,
            403,
            'Este menú no está disponible para tu empresa.'
        );

        // Horario de corte definido por la empresa cliente
        $deadline = $this->orderingDeadline($user->company, $menu->menu_date);
        if (now()->greaterThanOrEqualTo($deadline)) {
            return response()->json([
                'message' => "El plazo para pedir este menú venció el {$deadline->format('d-m-Y H:i')}.",
            ], 422);
        }

        // Seguridad alimentaria: bloquea si el plato contiene alérgenos declarados por el comensal
        $conflicts = collect($user->allergies ?? [])
            ->filter(fn (string $allergen) => $item->dish->containsAllergen($allergen))
            ->values();

        if ($conflicts->isNotEmpty() && !$request->boolean('accept_allergen_risk')) {
            return response()->json([
                'message' => 'Este plato contiene alérgenos declarados en tu perfil. Debes confirmar explícitamente para continuar.',
                'allergens' => $conflicts,
                'requires_confirmation' => true,
            ], 422);
        }

        $order = DB::transaction(function () use ($user, $item, $menu, $data) {
            // Lock del ítem para evitar sobreventa de cupos en pedidos concurrentes
            $lockedItem = MenuItem::whereKey($item->id)->lockForUpdate()->firstOrFail();

            if ($lockedItem->isSoldOut()) {
                abort(422, 'Esta opción ya no tiene cupos disponibles.');
            }

            $attributes = [
                'menu_id' => $menu->id,
                'menu_item_id' => $lockedItem->id,
                'branch_id' => $user->branch_id,
                'status' => OrderStatus::Confirmed,
                'copay_amount_clp' => $lockedItem->price_extra_clp,
                'notes' => $data['notes'] ?? null,
                'cancelled_at' => null,
                'cancellation_reason' => null,
            ];

            // La BD garantiza 1 pedido por comensal/día: si existe uno cancelado se reactiva,
            // si existe uno activo se rechaza.
            $existing = Order::withTrashed()
                ->where('user_id', $user->id)
                ->forDate($menu->menu_date)
                ->first();

            if ($existing) {
                if ($existing->status !== OrderStatus::Cancelled && !$existing->trashed()) {
                    abort(422, 'Ya tienes un pedido activo para este día. Cancélalo antes de crear uno nuevo.');
                }

                $existing->restore();
                $existing->update([...$attributes, 'qr_code_hash' => Order::generateUniqueQrHash()]);

                return $existing;
            }

            return Order::create([
                ...$attributes,
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'order_date' => $menu->menu_date,
            ]);
        });

        return response()->json($order->load(['menuItem.dish', 'menu:id,title,menu_date', 'branch:id,name']), 201);
    }

    /**
     * Muestra el detalle de un pedido, incluyendo el hash para generar el QR de retiro.
     *
     * GET /api/v1/orders/{order}
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);

        return response()->json(
            $order->load(['menuItem.dish', 'menu:id,title,menu_date', 'branch:id,name', 'user:id,name,email'])
        );
    }

    /**
     * Cancela un pedido:
     * - El comensal puede cancelar su propio pedido antes del horario de corte.
     * - El TenantAdmin puede cancelar en cualquier momento (ej: contingencia de cocina).
     *
     * POST /api/v1/orders/{order}/cancel
     */
    public function cancel(CancelOrderRequest $request, Order $order): JsonResponse
    {
        $this->authorize('cancel', $order);

        $user = $request->user();

        $data = $request->validated();

        if ($order->status !== OrderStatus::Confirmed) {
            return response()->json([
                'message' => 'Solo se pueden cancelar pedidos en estado confirmado.',
            ], 422);
        }

        if ($order->user_id === $user->id) {
            // El dueño solo puede cancelar dentro del plazo de corte
            $deadline = $this->orderingDeadline($order->company, $order->order_date);
            if (now()->greaterThanOrEqualTo($deadline)) {
                return response()->json([
                    'message' => "El plazo para cancelar venció el {$deadline->format('d-m-Y H:i')}.",
                ], 422);
            }
        }

        $order->cancel($data['reason'] ?? null);

        return response()->json($order->fresh());
    }

    /**
     * Calcula la fecha/hora límite para pedir o cancelar un menú de una fecha dada.
     */
    private function orderingDeadline(Company $company, Carbon|string $menuDate): Carbon
    {
        return Carbon::parse($menuDate)
            ->subDays($company->cutoff_days_in_advance ?? 0)
            ->setTimeFromTimeString($company->cutoff_time ?? '10:00');
    }
}
