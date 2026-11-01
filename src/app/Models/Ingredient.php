<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'faqs' => 'array',
            'grams_per_cup' => 'float',
            'guide_published' => 'boolean',
        ];
    }

    public function substitutes(): HasMany
    {
        return $this->hasMany(IngredientSubstitute::class)->where('is_active', true);
    }
}
