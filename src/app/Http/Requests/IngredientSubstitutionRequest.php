<?php

namespace App\Http\Requests;

use App\Services\IngredientConversionService;
use App\Services\IngredientSubstitutionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IngredientSubstitutionRequest extends FormRequest
{
    protected $redirectRoute = 'tools.ingredient-substitution';

    public function rules(): array
    {
        return [
            'ingredient' => ['required', 'string', 'min:2', 'max:80'],
            'purpose' => ['required', Rule::in(array_keys(IngredientSubstitutionService::PURPOSES))],
            'diet' => ['required', Rule::in(array_keys(IngredientSubstitutionService::DIETS))],
            'quantity' => ['nullable', 'numeric', 'gt:0', 'max:100000'],
            'unit' => ['nullable', 'required_with:quantity', Rule::in(array_keys(IngredientConversionService::UNITS))],
        ];
    }

    public function messages(): array
    {
        return [
            'ingredient.required' => 'Please enter the ingredient you want to replace.',
            'ingredient.min' => 'Please enter at least 2 characters.',
            'quantity.numeric' => 'The quantity must be a number.',
            'quantity.gt' => 'The quantity must be greater than zero.',
            'unit.required_with' => 'Please choose a unit for the quantity.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'purpose' => $this->input('purpose', 'general'),
            'diet' => $this->input('diet', 'none'),
            'ingredient' => is_string($this->input('ingredient')) ? trim($this->input('ingredient')) : $this->input('ingredient'),
            'quantity' => str_replace(',', '.', (string) $this->input('quantity', '')) ?: null,
        ]);
    }
}
