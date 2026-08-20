<?php

namespace App\Http\Requests\Company;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Rules\ValidIdentification;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
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
        $companyId = $this->route('company')?->id ?? $this->route('company') ?? $this->input('id');
        $tenantId = $tenantManager->getTenantId() ?? $this->input('tenant_id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'rut' => [
                'sometimes',
                'string',
                'max:30',
                ValidIdentification::forCurrentContext($companyId, $tenantId),
                Rule::unique('companies', 'rut')
                    ->where('tenant_id', $tenantId)
                    ->whereNull('deleted_at')
                    ->ignore($companyId),
            ],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'cutoff_time' => ['nullable', 'date_format:H:i,H:i:s'],
            'cutoff_days_in_advance' => ['nullable', 'integer', 'min:0', 'max:30'],
            'subsidy_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
            'settings.country' => ['nullable', 'string', 'max:10'],
        ];
    }
}
