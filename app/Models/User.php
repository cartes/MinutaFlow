<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    /**
     * Atributos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'company_id',
        'branch_id',
        'name',
        'email',
        'rut',
        'phone',
        'role',
        'dietary_preferences',
        'allergies',
        'password',
        'is_active',
    ];

    /**
     * Atributos ocultos en la serialización (ej: respuestas JSON/API).
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Conversiones de tipos (Casting) para los atributos del modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,       // Convierte automáticamente el string a enum UserRole
            'dietary_preferences' => 'array', // Array con tags de preferencias (ej: ['vegan', 'celiac'])
            'allergies' => 'array',           // Array con alérgenos (ej: ['mani', 'mariscos', 'lactosa'])
            'is_active' => 'boolean',
        ];
    }

    /* =========================================================================
     * RELACIONES ELOQUENT
     * ========================================================================= */

    /**
     * Empresa de catering (Tenant) al que pertenece el usuario.
     * (Es null en caso de un SuperAdmin global).
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Empresa cliente donde trabaja el usuario (ej: empresa corporativa con contrato de almuerzos).
     * (Aplica para roles CompanyAdmin y Employee).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Sucursal o sede física asignada al comensal para la entrega de su comida.
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Historial de pedidos/almuerzos realizados por el usuario.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /* =========================================================================
     * MÉTODOS DE COMPROBACIÓN DE ROLES Y PERMISOS
     * ========================================================================= */

    /**
     * Determina si el usuario es Super Administrador global de MinutaFlow.
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    /**
     * Determina si el usuario es Administrador del Catering (Tenant).
     */
    public function isTenantAdmin(): bool
    {
        return $this->role === UserRole::TenantAdmin;
    }

    /**
     * Determina si el usuario es Personal de Cocina o Despacho del catering.
     */
    public function isKitchenOperator(): bool
    {
        return $this->role === UserRole::KitchenOperator;
    }

    /**
     * Determina si el usuario es Administrador / RRHH de la Empresa Cliente.
     */
    public function isCompanyAdmin(): bool
    {
        return $this->role === UserRole::CompanyAdmin;
    }

    /**
     * Determina si el usuario es un Comensal / Empleado final.
     */
    public function isEmployee(): bool
    {
        return $this->role === UserRole::Employee;
    }

    /* =========================================================================
     * MÉTODOS DE NEGOCIO Y REGLAS NUTRICIONALES
     * ========================================================================= */

    /**
     * Verifica si el usuario tiene una alergia alimentaria específica.
     *
     * @param string $allergen Nombre del alérgeno a verificar (ej: "mani", "gluten")
     */
    public function hasAllergy(string $allergen): bool
    {
        if (empty($this->allergies) || !is_array($this->allergies)) {
            return false;
        }

        return in_array(mb_strtolower($allergen), array_map('mb_strtolower', $this->allergies), true);
    }

    /**
     * Obtiene el número de teléfono formateado o limpio para envíos de WhatsApp.
     */
    public function getCleanPhoneNumber(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        // Elimina espacios, guiones y caracteres no numéricos excepto el '+'
        return preg_replace('/[^\d+]/', '', $this->phone);
    }
}
