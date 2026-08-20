<?php

namespace App\Http\Requests\User;

use App\Enums\UserRole;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Rules\ValidIdentification;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreUserRequest extends FormRequest
{
    use SanitizesInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(TenantManager $tenantManager): array
    {
        $tenantId = $tenantManager->getTenantId() ?? $this->input('tenant_id');
        $companyId = $this->input('company_id');

        return [
            'tenant_id' => [
                'nullable',
                Rule::exists('tenants', 'id')->whereNull('deleted_at'),
            ],
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')
                    ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                    ->whereNull('deleted_at'),
            ],
            'branch_id' => [
                'nullable',
                Rule::exists('branches', 'id')
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')
                    ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                    ->whereNull('deleted_at'),
            ],
            'rut' => [
                'nullable',
                'string',
                'max:30',
                ValidIdentification::forCurrentContext($companyId, $tenantId),
                Rule::unique('users', 'rut')
                    ->when($tenantId, fn ($q) => $q->where('tenant_id', $tenantId))
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', new Enum(UserRole::class)],
            'dietary_preferences' => ['nullable', 'array'],
            'dietary_preferences.*' => ['string', 'max:50'],
            'allergies' => ['nullable', 'array'],
            'allergies.*' => ['string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
