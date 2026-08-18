<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Confirmed = 'confirmed'; // Pedido dentro de plazo [cite: 182]
    case Cancelled = 'cancelled'; // Cancelado antes del corte [cite: 182]
    case Delivered = 'delivered'; // Almuerzo escaneado/entregado [cite: 182]
    case NoShow = 'no_show';       // Pedido no retirado [cite: 182]
}
