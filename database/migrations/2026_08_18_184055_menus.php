<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Menú por fecha
        Schema::create('menus', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignUuid('company_id')->nullable()->constrained('companies')->cascadeOnDelete(); // Null = Menú común
            $table->string('title')->nullable(); // Ej: "Menú Ejecutivo", "Menú Fiestas Patrias"
            $table->date('menu_date');
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['tenant_id', 'company_id', 'menu_date']);
            $table->index(['tenant_id', 'menu_date']);
        });

        // Opciones dentro del menú diario
        Schema::create('menu_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignUuid('dish_id')->constrained('dishes')->restrictOnDelete();
            $table->string('option_label', 30); // Ej: "Opción A (Proteica)", "Opción Vegana" [cite: 13, 193]
            $table->unsignedSmallInteger('max_quota')->nullable(); // Cupo máximo por stock [cite: 193]
            $table->unsignedInteger('price_extra_clp')->default(0); // Copago extra [cite: 193]
            $table->boolean('is_available')->default(true);
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
