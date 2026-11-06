<?php

namespace App\Services\Nutrition;

use App\Models\Food;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FoodSearchService
{
    /**
     * Search foods by keyword (English, French, or aliases).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function search(string $query, int $limit = 20): Collection
    {
        $rawQuery = trim($query);
        if ($rawQuery === '') {
            return collect();
        }

        $needle = Str::lower($rawQuery);
        $escaped = addcslashes($needle, '%_\\');

        $foods = Food::query()
            ->with(['nutrients', 'source'])
            ->active()
            ->where(function ($q) use ($escaped, $needle) {
                $q->where('name_en', 'LIKE', "%{$escaped}%")
                    ->orWhere('name_fr', 'LIKE', "%{$escaped}%")
                    ->orWhere('name', 'LIKE', "%{$escaped}%")
                    ->orWhereJsonContains('aliases', $needle)
                    ->orWhere('aliases', 'LIKE', "%{$escaped}%");
            })
            ->get();

        return $foods
            ->sortBy(function (Food $food) use ($needle) {
                $en = Str::lower($food->name_en);
                $fr = Str::lower($food->name_fr);
                $canonical = Str::lower($food->name);

                // Exact match gets highest priority (score 0)
                if ($en === $needle || $fr === $needle || $canonical === $needle) {
                    return 0;
                }

                // Starts with needle gets score 1
                if (Str::startsWith($en, $needle) || Str::startsWith($fr, $needle)) {
                    return 1;
                }

                // Raw forms are prioritized over cooked if both match and user didn't specify cooked
                if (! Str::contains($needle, ['cook', 'cuit', 'roast', 'rôti', 'bake']) && $food->preparation_state === 'raw') {
                    return 2;
                }

                return 3;
            })
            ->take($limit)
            ->map(fn (Food $food) => $this->format($food))
            ->values();
    }

    /**
     * Format a food model into a presentation array.
     *
     * @return array<string, mixed>
     */
    public function format(Food $food): array
    {
        $nutrients = $food->nutrients;

        return [
            'id' => $food->id,
            'name' => $food->name_en,
            'name_fr' => $food->name_fr,
            'canonical_name' => $food->name,
            'category' => $food->category,
            'preparation_state' => $food->preparation_state,
            'source' => $food->source?->name ?? 'Standard Reference',
            'source_version' => $food->source?->version,
            'source_food_id' => $food->source_food_id,
            'density_g_per_ml' => $food->density_g_per_ml,
            'piece_weight_g' => $food->piece_weight_g,
            'supported_units' => array_keys($food->supportedUnits()),
            'units_map' => $food->supportedUnits(),
            'nutrients_per_100g' => [
                'calories' => (float) ($nutrients?->calories_kcal ?? 0.0),
                'protein' => (float) ($nutrients?->protein_g ?? 0.0),
                'carbs' => (float) ($nutrients?->carbohydrates_g ?? 0.0),
                'fat' => (float) ($nutrients?->fat_g ?? 0.0),
                'fiber' => (float) ($nutrients?->fiber_g ?? 0.0),
                'sugar' => (float) ($nutrients?->sugar_g ?? 0.0),
                'saturated_fat' => (float) ($nutrients?->saturated_fat_g ?? 0.0),
                'sodium' => (float) ($nutrients?->sodium_mg ?? 0.0),
            ],
        ];
    }
}
