<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientSubstitute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IngredientSubstitutionService
{
    public const PURPOSES = [
        'general' => 'General use',
        'baking' => 'Baking (cakes, cookies, bread)',
        'cooking' => 'Cooking (sauces, meals)',
        'desserts' => 'Desserts',
    ];

    public const DIETS = [
        'none' => 'No preference',
        'vegan' => 'Vegan',
        'dairy-free' => 'Dairy-free',
        'gluten-free' => 'Gluten-free',
        'nut-free' => 'Nut-free',
        'egg-free' => 'Egg-free',
        'vegetarian' => 'Vegetarian',
    ];

    /** Allergens that must never appear when the matching diet is selected. */
    private const EXCLUDED_ALLERGENS = [
        'dairy-free' => ['milk'],
        'egg-free' => ['egg'],
        'nut-free' => ['tree nuts', 'coconut', 'peanuts'],
        'gluten-free' => ['wheat', 'gluten'],
    ];

    private const CACHE_KEY = 'ingredient-substitution.catalog.v2';

    public function __construct(private IngredientConversionService $conversion) {}

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return Collection<int, Ingredient> */
    public function catalog(): Collection
    {
        // Raw attributes are cached (not models) so the cache store never needs to unserialize classes.
        $rows = Cache::remember(self::CACHE_KEY, 3600, fn () => Ingredient::with('substitutes')->orderBy('name')->get()
            ->map(fn (Ingredient $i) => [
                'attributes' => $i->getAttributes(),
                'substitutes' => $i->substitutes->map(fn ($s) => $s->getAttributes())->all(),
            ])->all());

        return collect($rows)->map(function (array $row) {
            $ingredient = (new Ingredient)->newFromBuilder($row['attributes']);
            $ingredient->setRelation('substitutes', collect($row['substitutes'])->map(fn ($a) => (new IngredientSubstitute)->newFromBuilder($a)));

            return $ingredient;
        });
    }

    public function find(string $query): ?Ingredient
    {
        $needle = $this->normalize($query);
        if ($needle === '') {
            return null;
        }
        $needles = array_unique([$needle, Str::singular($needle)]);

        return $this->catalog()->first(function (Ingredient $i) use ($needles) {
            $names = array_map(fn ($n) => $this->normalize($n), array_merge([$i->name, $i->slug], $i->aliases ?? []));

            return (bool) array_intersect($needles, $names);
        });
    }

    public function findBySlug(string $slug): ?Ingredient
    {
        return $this->catalog()->firstWhere('slug', $slug);
    }

    /** Catalogue names only, never a fuzzy guess at a substitute. */
    public function suggestions(string $query, int $limit = 5): array
    {
        $needle = $this->normalize($query);
        if (mb_strlen($needle) < 2) {
            return [];
        }

        return $this->catalog()
            ->filter(fn (Ingredient $i) => str_contains($this->normalize($i->name), $needle) || str_contains($needle, $this->normalize($i->name)))
            ->take($limit)->pluck('name')->all();
    }

    /**
     * @return array{status:string, ingredient:?Ingredient, items:array, suggestions:array, query:string}
     */
    public function search(string $query, string $purpose, string $diet, ?float $qty = null, ?string $unit = null): array
    {
        $ingredient = $this->find($query);
        if (! $ingredient) {
            return ['status' => 'unknown', 'ingredient' => null, 'items' => [], 'suggestions' => $this->suggestions($query), 'query' => $query];
        }

        $subs = $ingredient->substitutes
            ->filter(fn ($s) => $purpose === 'general' || in_array($purpose, $s->purposes, true))
            ->filter(fn ($s) => $this->matchesDiet($s->dietary_tags, $s->allergens, $diet))
            ->sortByDesc(fn ($s) => $s->quality_score + (($s->purposes[0] ?? null) === $purpose ? 10 : 0))
            ->values();

        $items = $subs->map(function ($s, $i) use ($ingredient, $qty, $unit) {
            $scaled = $qty && $unit
                ? $this->conversion->scale($s->components, $s->original_quantity, $s->original_unit, $qty, $unit, $ingredient->grams_per_cup)
                : null;

            return ['model' => $s, 'best' => $i === 0, 'scaled' => $scaled];
        })->all();

        return [
            'status' => $items ? 'found' : 'none',
            'ingredient' => $ingredient,
            'items' => $items,
            'suggestions' => [],
            'query' => $query,
        ];
    }

    private function matchesDiet(array $tags, array $allergens, string $diet): bool
    {
        if ($diet === 'none' || $diet === '') {
            return true;
        }
        if (! in_array($diet, $tags, true)) {
            return false;
        }

        return ! array_intersect(self::EXCLUDED_ALLERGENS[$diet] ?? [], $allergens);
    }

    private function normalize(string $s): string
    {
        $s = Str::lower(trim($s));

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^\p{L}\p{N}\s-]/u', '', $s)));
    }
}
