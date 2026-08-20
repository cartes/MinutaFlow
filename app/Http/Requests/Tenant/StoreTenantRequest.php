<?php

namespace App\Http\Requests\Tenant;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Rules\ValidIdentification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTenantRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('tenants', 'slug')->whereNull('deleted_at')],
            'rut' => [
                'required',
                'string',
                'max:30',
                ValidIdentification::forCurrentContext(),
                Rule::unique('tenants', 'rut')->whereNull('deleted_at'),
            ],
            'billing_email' => ['required', 'string', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'timezone' => ['nullable', 'string', 'max:50', 'timezone'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['boolean'],
            'settings' => ['nullable', 'array'],
            'settings.country' => ['nullable', 'string', 'max:10'],
        ];
    }
}
