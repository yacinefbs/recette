<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientSubstitute extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purposes' => 'array',
            'components' => 'array',
            'dietary_tags' => 'array',
            'allergens' => 'array',
            'original_quantity' => 'float',
            'is_active' => 'boolean',
            'verified_on' => 'date',
        ];
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
