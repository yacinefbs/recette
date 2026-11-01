<?php

namespace App\Services\MealPlanner;

use App\Models\Recipe;

final class ShoppingListService
{
    public function __construct(private readonly IngredientNormalizer $normalizer) {}

    /**
     * @param  list<int>  $recipeIds  one entry per planned meal (a recipe planned twice is listed twice)
     * @return list<array{category:string,items:list<array{key:string,name:string,quantity:string}>}>
     */
    public function build(array $recipeIds, int $servings): array
    {
        $times = array_count_values($recipeIds);
        $recipes = Recipe::query()->select('id', 'num_servings', 'sections')->whereIn('id', array_keys($times))->get();

        $agg = [];
        foreach ($recipes as $recipe) {
            $factor = $servings / max(1, (int) $recipe->num_servings) * $times[$recipe->id];
            foreach (collect($recipe->sections ?? [])->flatMap(fn ($s) => $s['components'] ?? []) as $component) {
                $c = $this->normalizer->component($component);
                if (! $c) {
                    continue;
                }
                $item = &$agg[$c['key']];
                $item ??= ['name' => $c['name'], 'parts' => [], 'unmeasured' => false];
                if ($c['dim'] === 'unknown') {
                    $item['unmeasured'] = true;
                } else {
                    $bucket = $c['dim'].'|'.$c['unit'];
                    $item['parts'][$bucket] = ($item['parts'][$bucket] ?? 0) + $c['qty'] * $factor;
                }
                unset($item);
            }
        }

        $groups = [];
        foreach ($agg as $key => $item) {
            $parts = [];
            foreach ($item['parts'] as $bucket => $qty) {
                [$dim, $unit] = explode('|', $bucket, 2);
                $parts[] = $this->normalizer->format($dim, $qty, $unit);
            }
            if ($item['unmeasured'] && $parts !== []) {
                $parts[] = 'plus some as needed';
            }

            $groups[$this->normalizer->category($item['name'])][] = [
                'key' => (string) $key,
                'name' => ucfirst($item['name']),
                'quantity' => implode(' + ', $parts),
            ];
        }

        $out = [];
        foreach (IngredientNormalizer::CATEGORY_ORDER as $category) {
            if (isset($groups[$category])) {
                usort($groups[$category], fn ($a, $b) => strcasecmp($a['name'], $b['name']));
                $out[] = ['category' => $category, 'items' => $groups[$category]];
            }
        }

        return $out;
    }
}
