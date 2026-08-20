<?php

namespace App\Http\Requests\Menu;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Services\Tenancy\TenantManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuRequest extends FormRequest
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
        $tenantId = $tenantManager->getTenantId() ?? $this->user()?->tenant_id;

        return [
            'company_id' => [
                'nullable',
                Rule::exists('companies', 'id')->where('tenant_id', $tenantId),
            ],
            'title' => ['nullable', 'string', 'max:255'],
            'menu_date' => [
                'required',
                'date',
                Rule::unique('menus', 'menu_date')
                    ->where('tenant_id', $tenantId)
                    ->where(fn ($q) => $this->filled('company_id')
                        ? $q->where('company_id', $this->input('company_id'))
                        : $q->whereNull('company_id')
                    )
                    ->whereNull('deleted_at'),
            ],
            'is_published' => ['boolean'],
            'items' => ['nullable', 'array'],
            'items.*.dish_id' => [
                'required',
                Rule::exists('dishes', 'id')->where('tenant_id', $tenantId)->whereNull('deleted_at'),
            ],
            'items.*.option_label' => ['required', 'string', 'max:30'],
            'items.*.max_quota' => ['nullable', 'integer', 'min:1'],
            'items.*.price_extra_clp' => ['nullable', 'integer', 'min:0'],
            'items.*.is_available' => ['boolean'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
