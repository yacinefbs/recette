<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodNutrient extends Model
{
    protected $fillable = [
        'food_id',
        'calories_kcal',
        'protein_g',
        'carbohydrates_g',
        'fat_g',
        'fiber_g',
        'sugar_g',
        'saturated_fat_g',
        'sodium_mg',
        'data_per_100g',
        'data_completeness',
    ];

    protected function casts(): array
    {
        return [
            'calories_kcal' => 'float',
            'protein_g' => 'float',
            'carbohydrates_g' => 'float',
            'fat_g' => 'float',
            'fiber_g' => 'float',
            'sugar_g' => 'float',
            'saturated_fat_g' => 'float',
            'sodium_mg' => 'float',
            'data_per_100g' => 'boolean',
            'data_completeness' => 'float',
        ];
    }

    public function food(): BelongsTo
    {
        return $this->belongsTo(Food::class);
    }
}
