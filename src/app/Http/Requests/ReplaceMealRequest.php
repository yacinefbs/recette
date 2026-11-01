<?php

namespace App\Http\Requests;

use App\DTO\MealPlanPreferences;
use Illuminate\Validation\Rule;

class ReplaceMealRequest extends GenerateMealPlanRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'meal' => ['required', Rule::in(MealPlanPreferences::MEALS)],
            'current_id' => ['nullable', 'integer', 'min:1'],
            'used_ids' => ['nullable', 'array', 'max:50'],
            'used_ids.*' => ['integer', 'min:1'],
        ];
    }
}
