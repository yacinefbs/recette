<?php

namespace App\Http\Requests;

use App\DTO\MealPlanPreferences;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateMealPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'servings' => ['required', 'integer', 'between:1,12'],
            'diet' => ['required', Rule::in(MealPlanPreferences::DIETS)],
            'budget' => ['required', Rule::in(MealPlanPreferences::BUDGETS)],
            'meals' => ['required', 'array', 'min:1', 'max:4'],
            'meals.*' => ['distinct', Rule::in(MealPlanPreferences::MEALS)],
            'days' => ['required', Rule::in([3, 5, 7])],
            'avoid' => ['nullable', 'string', 'max:300'],
            'have' => ['nullable', 'string', 'max:300'],
            'seed' => ['nullable', 'integer', 'min:0', 'max:2147483647'],
            'locked' => ['nullable', 'array', 'max:28'],
            'locked.*.day' => ['required', 'integer', 'between:1,7'],
            'locked.*.meal' => ['required', Rule::in(MealPlanPreferences::MEALS)],
            'locked.*.recipe_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function preferences(): MealPlanPreferences
    {
        return MealPlanPreferences::fromArray($this->validated());
    }
}
