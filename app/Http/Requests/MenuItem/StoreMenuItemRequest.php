<?php

namespace App\Http\Requests\MenuItem;

use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
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
            'dish_id' => [
                'required',
                Rule::exists('dishes', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'option_label' => ['required', 'string', 'max:30'],
            'max_quota' => ['nullable', 'integer', 'min:1'],
            'price_extra_clp' => ['nullable', 'integer', 'min:0'],
            'is_available' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
