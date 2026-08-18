<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Escanea el QR del comprobante y marca el pedido como entregado.
     * Lo usa el personal de cocina/despacho en el punto de entrega.
     *
     * POST /api/v1/delivery/scan  { "qr_code_hash": "..." }
     */
    public function scan(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(
            $user->isKitchenOperator() || $user->isTenantAdmin(),
            403,
            'Solo el personal del catering puede registrar entregas.'
        );

        $data = $request->validate([
            'qr_code_hash' => ['required', 'string', 'size:64'],
        ]);

        $order = Order::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('qr_code_hash', $data['qr_code_hash'])
            ->with(['user:id,name', 'menuItem.dish:id,name', 'branch:id,name'])
            ->first();

        if (!$order) {
            return response()->json(['message' => 'Código QR no válido o pedido inexistente.'], 404);
        }

        // Validaciones del estado del pedido antes de entregar
        if ($order->status === OrderStatus::Delivered) {
            return response()->json([
                'message' => "Este pedido ya fue entregado el {$order->delivered_at->format('d-m-Y H:i')}.",
                'order' => $order,
            ], 409);
        }

        if ($order->status === OrderStatus::Cancelled) {
            return response()->json([
                'message' => 'Este pedido fue cancelado y no debe entregarse.',
                'order' => $order,
            ], 409);
        }

        if (!$order->order_date->isToday()) {
            return response()->json([
                'message' => "Este pedido corresponde al {$order->order_date->format('d-m-Y')}, no a hoy.",
                'order' => $order,
            ], 409);
        }

        $order->markAsDelivered();

        return response()->json([
            'message' => "Entrega registrada: {$order->user->name} — {$order->menuItem->dish->name}.",
            'order' => $order->fresh(['user:id,name', 'menuItem.dish:id,name', 'branch:id,name']),
        ]);
    }
}
