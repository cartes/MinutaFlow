<?php

use App\Enums\OrderStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Claves de contexto y trazabilidad
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('menu_id')->constrained('menus')->restrictOnDelete();
            $table->foreignUuid('menu_item_id')->constrained('menu_items')->restrictOnDelete();

            $table->date('order_date');
            $table->string('status')->default(OrderStatus::Confirmed->value);

            // Token único para comprobante y QR de recepción [cite: 16, 195]
            $table->string('qr_code_hash', 64)->unique();
            $table->unsignedInteger('copay_amount_clp')->default(0);
            $table->text('notes')->nullable(); // Notas especiales o comentarios de entrega
            $table->timestamp('delivered_at')->nullable(); // Proof of delivery [cite: 16]
            $table->timestamp('cancelled_at')->nullable(); // Fecha de cancelación
            $table->string('cancellation_reason')->nullable(); // Motivo de cancelación
            $table->timestamps();
            $table->softDeletes();

            // Garantiza que un comensal solo tenga 1 almuerzo por día
            $table->unique(['user_id', 'order_date']);

            // Índices para reportes y packing list diario
            $table->index(['tenant_id', 'order_date']);
            $table->index(['company_id', 'branch_id', 'order_date']);
            $table->index(['status', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
