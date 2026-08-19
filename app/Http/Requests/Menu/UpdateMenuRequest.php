<?php

namespace App\Http\Requests\Menu;

use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(TenantManager $tenantManager): array
    {
        $tenantId = $tenantManager->getTenantId();

        return [
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('tenant_id', $tenantId),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'menu_date' => ['sometimes', 'date'],
            'is_published' => ['boolean'],
        ];
    }
}
