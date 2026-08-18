<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('dishes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category', 100); // Fondo, Hipocalórico, Ensalada, Postre [cite: 190]

            // Información Nutricional y Alérgenos
            $table->json('dietary_tags')->nullable();
            $table->json('allergens')->nullable();
            $table->unsignedSmallInteger('calories_kcal')->nullable();

            // Costos internos (Food Cost)
            $table->unsignedInteger('raw_cost_clp')->default(0);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dishes');
    }
};
