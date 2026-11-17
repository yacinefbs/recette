<?php

namespace App\Services;

use App\Models\Recipe;
use App\Services\MealPlanner\IngredientNormalizer;

final class PantryRecipeMatcher
{
    /**
     * @param array<int, array{name:string,quantity:float|string,unit:string,expiry_date?:string|null}> $ingredients
     * @return list<array<string, mixed>>
     */
    public function match(array $ingredients, bool $prioritizeExpiring = false): array
    {
        $pantry = collect($ingredients)
            ->filter(fn (array $item) => (float) $item['quantity'] > 0)
            ->values();

        if ($pantry->isEmpty()) {
            return [];
        }

        $today = now()->startOfDay();
        $entries = Recipe::query()
            ->select('id', 'name', 'image_path', 'thumbnail_url', 'sections', 'total_time_minutes')
            ->cursor()
            ->map(function (Recipe $recipe) use ($pantry, $prioritizeExpiring, $today) {
                $components = collect($recipe->sections ?? [])
                    ->flatMap(fn ($section) => $section['components'] ?? [])
                    ->filter(fn ($component) => $this->componentName($component) !== '')
                    ->unique(fn ($component) => $this->normalize($this->componentName($component)))
                    ->values();

                if ($components->isEmpty()) {
                    return null;
                }

                $matched = collect();
                $missing = collect();
                $expiringMatches = 0;

                foreach ($components as $component) {
                    $componentName = $this->componentName($component);
                    $matches = $pantry->filter(fn (array $item) => $this->sameIngredient($item['name'], $componentName));

                    if ($matches->isEmpty()) {
                        $missing->push($component['raw_text'] ?? $componentName);
                        continue;
                    }

                    $isExpiringSoon = $matches->contains(fn (array $item) => $this->isExpiringSoon($item['expiry_date'] ?? null, $today));
                    if ($isExpiringSoon) {
                        $expiringMatches++;
                    }
                    $match = $matches->first(fn (array $item) => $this->isExpiringSoon($item['expiry_date'] ?? null, $today)) ?? $matches->first();

                    $matched->push([
                        'name' => $componentName,
                        'inventory_name' => $match['name'],
                        'quantity_sufficient' => $this->quantitySufficient($matches, $component),
                        'expiring_soon' => $isExpiringSoon,
                    ]);
                }

                if ($matched->isEmpty()) {
                    return null;
                }

                $total = $components->count();

                return [
                    'recipe' => [
                        'id' => $recipe->id,
                        'name' => $recipe->name,
                        'url' => route('recipes.show', $recipe),
                        'image' => $recipe->image,
                        'time' => $recipe->total_time_minutes,
                    ],
                    'matched' => $matched->all(),
                    'missing' => $missing->values()->all(),
                    'matched_count' => $matched->count(),
                    'total_ingredients' => $total,
                    'expiring_match_count' => $expiringMatches,
                    'match_ratio' => $matched->count() / $total,
                ];
            })
            ->filter()
            ->sort(function (array $a, array $b) use ($prioritizeExpiring) {
                if ($prioritizeExpiring && $a['expiring_match_count'] !== $b['expiring_match_count']) {
                    return $b['expiring_match_count'] <=> $a['expiring_match_count'];
                }

                return ($b['match_ratio'] <=> $a['match_ratio'])
                    ?: ($b['matched_count'] <=> $a['matched_count'])
                    ?: ($a['recipe']['id'] <=> $b['recipe']['id']);
            })
            ->take(24)
            ->values()
            ->map(fn (array $entry) => collect($entry)->except(['expiring_match_count', 'match_ratio'])->all());

        return $entries->all();
    }

    private function componentName(array $component): string
    {
        $name = trim((string) ($component['ingredient']['display_singular'] ?? $component['ingredient']['name'] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return trim((string) preg_replace(
            '/^[\d\s\/.¼½¾⅓⅔⅛⅜⅝⅞]+(?:[a-z]+\.?\s+)?/iu',
            '',
            (string) ($component['raw_text'] ?? '')
        ));
    }

    private function normalize(string $name): string
    {
        $name = mb_strtolower(trim($name));
        $name = strtr($name, ['&' => ' and ']);
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $name) ?? '';
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return implode(' ', array_map(function (string $word): string {
            if (mb_strlen($word) > 4 && str_ends_with($word, 'ies')) {
                return mb_substr($word, 0, -3).'y';
            }
            if (mb_strlen($word) > 3 && str_ends_with($word, 'oes')) {
                return mb_substr($word, 0, -2);
            }
            if (mb_strlen($word) > 3 && str_ends_with($word, 's') && ! str_ends_with($word, 'ss')) {
                return mb_substr($word, 0, -1);
            }

            return $word;
        }, $words));
    }

    private function sameIngredient(string $pantryName, string $recipeName): bool
    {
        $pantryName = $this->normalize($pantryName);
        $recipeName = $this->normalize($recipeName);

        return $pantryName !== '' && $recipeName !== ''
            && ($pantryName === $recipeName
                || str_contains(' '.$recipeName.' ', ' '.$pantryName.' ')
                || str_contains(' '.$pantryName.' ', ' '.$recipeName.' '));
    }

    private function isExpiringSoon(?string $date, \Illuminate\Support\Carbon $today): bool
    {
        if (! $date) {
            return false;
        }

        $expiry = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $date)->startOfDay();

        return $expiry->greaterThanOrEqualTo($today) && $expiry->lessThanOrEqualTo($today->copy()->addDays(7));
    }

    private function quantitySufficient(\Illuminate\Support\Collection $items, array $component): ?bool
    {
        $measurement = collect($component['measurements'] ?? [])
            ->first(fn ($candidate) => IngredientNormalizer::parseQuantity($candidate['quantity'] ?? '') > 0);

        if (! $measurement) {
            return null;
        }

        $recipe = $this->comparableQuantity(
            IngredientNormalizer::parseQuantity($measurement['quantity'] ?? ''),
            (string) ($measurement['unit']['name'] ?? '')
        );

        if (! $recipe) {
            return null;
        }

        $comparable = $items
            ->map(fn (array $item) => $this->comparableQuantity((float) $item['quantity'], (string) $item['unit']))
            ->filter(fn (?array $quantity) => $quantity !== null && $quantity['dimension'] === $recipe['dimension'])
            ->values();

        return $comparable->isEmpty() ? null : $comparable->sum('amount') >= $recipe['amount'];
    }

    /** @return array{dimension:string,amount:float}|null */
    private function comparableQuantity(float $quantity, string $unit): ?array
    {
        $unit = mb_strtolower(trim($unit));
        $unit = rtrim($unit, '.');
        $mass = [
            'g' => 1, 'gram' => 1, 'grams' => 1, 'kg' => 1000, 'kilogram' => 1000, 'kilograms' => 1000,
            'ounce' => 28.3495, 'ounces' => 28.3495, 'oz' => 28.3495, 'pound' => 453.592, 'pounds' => 453.592, 'lb' => 453.592,
        ];
        $volume = [
            'ml' => 1, 'milliliter' => 1, 'milliliters' => 1, 'l' => 1000, 'liter' => 1000, 'liters' => 1000,
            'cup' => 236.588, 'cups' => 236.588, 'tablespoon' => 14.7868, 'tablespoons' => 14.7868,
            'teaspoon' => 4.92892, 'teaspoons' => 4.92892, 'fluid ounce' => 29.5735, 'fluid ounces' => 29.5735,
            'pint' => 473.176, 'pints' => 473.176, 'quart' => 946.353, 'quarts' => 946.353,
            'gallon' => 3785.41, 'gallons' => 3785.41,
        ];

        if (isset($mass[$unit])) {
            return ['dimension' => 'mass', 'amount' => $quantity * $mass[$unit]];
        }
        if (isset($volume[$unit])) {
            return ['dimension' => 'volume', 'amount' => $quantity * $volume[$unit]];
        }

        $countAliases = [
            'piece' => 'piece', 'pieces' => 'piece', 'pc' => 'piece', 'pcs' => 'piece', 'each' => 'piece', 'whole' => 'piece',
            'pack' => 'pack', 'packs' => 'pack', 'can' => 'can', 'cans' => 'can',
            'bottle' => 'bottle', 'bottles' => 'bottle', 'bag' => 'bag', 'bags' => 'bag',
        ];
        $unit = $countAliases[$unit] ?? $unit;

        return $unit === '' ? null : ['dimension' => 'count:'.$unit, 'amount' => $quantity];
    }
}
