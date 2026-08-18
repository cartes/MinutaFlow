<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->string('rut', 20);
            $table->string('billing_email')->nullable();
            $table->string('billing_address')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();

            // Reglas de horario de corte (Cut-off) y subsidios
            $table->time('cutoff_time')->default('17:00:00');
            $table->unsignedTinyInteger('cutoff_days_in_advance')->default(1);
            $table->decimal('subsidy_percentage', 5, 2)->default(100.00);

            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Restricción: Un catering no puede duplicar el RUT de un mismo cliente
            $table->unique(['tenant_id', 'rut']);
            $table->index(['tenant_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
