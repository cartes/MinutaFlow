<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeliveryController;
use App\Http\Controllers\Api\DishController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    /* -------------------------------------------------------------------------
     * Autenticación (públicas)
     * ---------------------------------------------------------------------- */
    Route::post('auth/login', [AuthController::class, 'login']);

    /* -------------------------------------------------------------------------
     * Rutas protegidas con token Sanctum y contexto de Tenant
     * ---------------------------------------------------------------------- */
    Route::middleware(['auth:sanctum', 'tenant.context'])->group(function () {

        // Sesión y perfil
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Catálogo de platos del catering
        Route::apiResource('dishes', DishController::class);

        // Menús diarios y sus opciones
        Route::apiResource('menus', MenuController::class);
        Route::post('menus/{menu}/publish', [MenuController::class, 'publish']);
        Route::post('menus/{menu}/unpublish', [MenuController::class, 'unpublish']);
        Route::post('menus/{menu}/items', [MenuItemController::class, 'store']);
        Route::match(['put', 'patch'], 'menu-items/{menuItem}', [MenuItemController::class, 'update']);
        Route::delete('menu-items/{menuItem}', [MenuItemController::class, 'destroy']);

        // Pedidos de comensales
        Route::apiResource('orders', OrderController::class)->only(['index', 'store', 'show']);
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

        // Entrega / escaneo de QR en recepción
        Route::post('delivery/scan', [DeliveryController::class, 'scan']);
    });
});
