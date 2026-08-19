<?php

namespace App\Http\Requests\Dish;

class UpdateDishRequest extends StoreDishRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['sometimes', 'string', 'max:255'];

        return $rules;
    }
}
