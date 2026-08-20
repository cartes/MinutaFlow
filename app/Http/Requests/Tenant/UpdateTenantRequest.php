<?php

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Rules\ValidIdentification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantRequest extends FormRequest
{
    use SanitizesInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $tenantId = $this->route('tenant')?->id ?? $this->route('tenant') ?? $this->input('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('tenants', 'slug')->whereNull('deleted_at')->ignore($tenantId),
            ],
            'rut' => [
                'sometimes',
                'string',
                'max:30',
                ValidIdentification::forCurrentContext(null, $tenantId),
                Rule::unique('tenants', 'rut')->whereNull('deleted_at')->ignore($tenantId),
            ],
            'billing_email' => ['sometimes', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:50', 'timezone'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
            'settings.country' => ['nullable', 'string', 'max:10'],
        ];
    }
}
