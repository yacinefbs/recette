<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Food extends Model
{
    protected $table = 'foods';

    protected $fillable = [
        'nutrition_source_id',
        'source_food_id',
        'name',
        'name_en',
        'name_fr',
        'category',
        'preparation_state',
        'density_g_per_ml',
        'piece_weight_g',
        'cup_weight_g',
        'tbsp_weight_g',
        'tsp_weight_g',
        'aliases',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'density_g_per_ml' => 'float',
            'piece_weight_g' => 'float',
            'cup_weight_g' => 'float',
            'tbsp_weight_g' => 'float',
            'tsp_weight_g' => 'float',
            'aliases' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(NutritionSource::class, 'nutrition_source_id');
    }

    public function nutrients(): HasOne
    {
        return $this->hasOne(FoodNutrient::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Returns an array of supported non-metric units with their conversion to grams.
     *
     * @return array<string, float>
     */
    public function supportedUnits(): array
    {
        $units = [
            'g' => 1.0,
            'kg' => 1000.0,
            'oz' => 28.3495,
            'lb' => 453.592,
        ];

        if ($this->density_g_per_ml !== null && $this->density_g_per_ml > 0) {
            $units['ml'] = $this->density_g_per_ml;
            $units['l'] = $this->density_g_per_ml * 1000.0;
        }

        if ($this->cup_weight_g !== null && $this->cup_weight_g > 0) {
            $units['cup'] = $this->cup_weight_g;
        } elseif (isset($units['ml'])) {
            $units['cup'] = $this->density_g_per_ml * 240.0;
        }

        if ($this->tbsp_weight_g !== null && $this->tbsp_weight_g > 0) {
            $units['tbsp'] = $this->tbsp_weight_g;
        } elseif (isset($units['ml'])) {
            $units['tbsp'] = $this->density_g_per_ml * 15.0;
        }

        if ($this->tsp_weight_g !== null && $this->tsp_weight_g > 0) {
            $units['tsp'] = $this->tsp_weight_g;
        } elseif (isset($units['ml'])) {
            $units['tsp'] = $this->density_g_per_ml * 5.0;
        }

        if ($this->piece_weight_g !== null && $this->piece_weight_g > 0) {
            $units['piece'] = $this->piece_weight_g;
            $units['unit'] = $this->piece_weight_g;
            $units['slice'] = $this->piece_weight_g;
        }

        return $units;
    }
}
