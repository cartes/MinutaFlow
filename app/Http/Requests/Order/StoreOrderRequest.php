<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
{
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
            'menu_item_id' => ['required', Rule::exists('menu_items', 'id')],
            'notes' => ['nullable', 'string', 'max:500'],
            'accept_allergen_risk' => ['boolean'],
        ];
    }
}
