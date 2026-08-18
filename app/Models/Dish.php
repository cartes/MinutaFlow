<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dish extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'category',
        'dietary_tags',
        'allergens',
        'calories_kcal',
        'raw_cost_clp',
        'image_url',
        'is_active',
    ];

    /**
     * Conversiones de tipos (Casting) para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dietary_tags' => 'array',    // Tags (ej: ['vegan', 'keto', 'gluten_free'])
            'allergens' => 'array',       // Alérgenos presentes (ej: ['huevo', 'soya', 'lactosa'])
            'calories_kcal' => 'integer', // Calorías en kcal
            'raw_cost_clp' => 'integer',  // Costo materia prima / Food cost en pesos chilenos
            'is_active' => 'boolean',
        ];
    }

    /* =========================================================================
     * RELACIONES ELOQUENT
     * ========================================================================= */

    /**
     * Empresa de catering (Tenant) propietaria de esta receta/plato.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Apariciones de este plato dentro de las opciones de menús diarios (menu_items).
     */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }

    /* =========================================================================
     * LOCAL SCOPES (Consultas predefinidas y reutilizables)
     * ========================================================================= */

    /**
     * Scope para filtrar únicamente platos habilitados para producción/menús.
     * Uso: Dish::active()->get();
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para filtrar platos por categoría (ej: "Fondo", "Hipocalórico", "Ensalada", "Postre").
     * Uso: Dish::byCategory('Fondo')->get();
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /* =========================================================================
     * HELPERS / MÉTODOS DE NEGOCIO
     * ========================================================================= */

    /**
     * Comprueba si el plato contiene un alérgeno específico que pueda ser peligroso para un usuario.
     *
     * @param string $allergen Nombre del alérgeno a comprobar
     */
    public function containsAllergen(string $allergen): bool
    {
        if (empty($this->allergens) || !is_array($this->allergens)) {
            return false;
        }

        return in_array(mb_strtolower($allergen), array_map('mb_strtolower', $this->allergens), true);
    }

    /**
     * Comprueba si el plato es apto para una preferencia alimentaria (ej: vegano, celíaco).
     *
     * @param string $tag Etiqueta dietética
     */
    public function hasDietaryTag(string $tag): bool
    {
        if (empty($this->dietary_tags) || !is_array($this->dietary_tags)) {
            return false;
        }

        return in_array(mb_strtolower($tag), array_map('mb_strtolower', $this->dietary_tags), true);
    }
}
