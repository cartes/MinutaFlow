<?php

namespace App\Http\Requests\Dish;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Foundation\Http\FormRequest;

class StoreDishRequest extends FormRequest
{
    use SanitizesInput;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'dietary_tags' => ['nullable', 'array'],
            'dietary_tags.*' => ['string', 'max:50'],
            'allergens' => ['nullable', 'array'],
            'allergens.*' => ['string', 'max:50'],
            'calories_kcal' => ['nullable', 'integer', 'min:0'],
            'raw_cost_clp' => ['nullable', 'integer', 'min:0'],
            'image_url' => ['nullable', 'url', 'max:2048'],
            'is_active' => ['boolean'],
        ];
    }
}
