<?php

namespace App\Services\Nutrition;

use App\Models\Food;
use InvalidArgumentException;

class NutritionCalculatorService
{
    public const KCAL_PER_G_PROTEIN = 4.0;
    public const KCAL_PER_G_CARBS = 4.0;
    public const KCAL_PER_G_FAT = 9.0;

    /**
     * Calculate nutrition totals and per-serving amounts.
     *
     * @param array<int, array{food_id: int|string, quantity: float|int|string, unit: string}> $items
     * @param int $servings
     * @return array<string, mixed>
     */
    public function calculate(array $items, int $servings = 1): array
    {
        if ($servings < 1) {
            throw new InvalidArgumentException('Number of servings must be at least 1.');
        }

        $foodIds = collect($items)->pluck('food_id')->filter()->unique()->all();
        $foods = Food::with(['nutrients', 'source'])->whereIn('id', $foodIds)->get()->keyBy('id');

        $ingredientBreakdown = [];
        $warnings = [];
        $sourcesUsed = [];

        $totalGrams = 0.0;
        $totalCalories = 0.0;
        $totalProtein = 0.0;
        $totalCarbs = 0.0;
        $totalFat = 0.0;
        $totalFiber = 0.0;
        $totalSugar = 0.0;
        $totalSatFat = 0.0;
        $totalSodium = 0.0;

        foreach ($items as $index => $item) {
            $foodId = (int) ($item['food_id'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            $unit = strtolower(trim((string) ($item['unit'] ?? 'g')));

            if ($qty <= 0) {
                continue;
            }

            /** @var Food|null $food */
            $food = $foods->get($foodId);
            if (! $food) {
                $warnings[] = [
                    'index' => $index,
                    'message' => "Ingredient #{$index} was not found in the nutritional database. Nutritional data unavailable.",
                ];
                continue;
            }

            $nutrients = $food->nutrients;
            if (! $nutrients) {
                $warnings[] = [
                    'index' => $index,
                    'food' => $food->name_en,
                    'message' => "Nutritional data is missing for {$food->name_en}. Values were not estimated.",
                ];
                continue;
            }

            // Convert to grams
            $grams = $this->convertToGrams($food, $qty, $unit);
            if ($grams === null) {
                $warnings[] = [
                    'index' => $index,
                    'food' => $food->name_en,
                    'unit' => $unit,
                    'message' => "Cannot convert unit '{$unit}' to weight for {$food->name_en} without reliable density or piece weight. No calories were invented.",
                ];
                continue;
            }

            // Factor per 100g
            $factor = $grams / 100.0;

            $cal = round($nutrients->calories_kcal * $factor, 1);
            $prot = round($nutrients->protein_g * $factor, 1);
            $carbs = round($nutrients->carbohydrates_g * $factor, 1);
            $fat = round($nutrients->fat_g * $factor, 1);
            $fib = round(($nutrients->fiber_g ?? 0.0) * $factor, 1);
            $sug = round(($nutrients->sugar_g ?? 0.0) * $factor, 1);
            $sat = round(($nutrients->saturated_fat_g ?? 0.0) * $factor, 1);
            $sod = round(($nutrients->sodium_mg ?? 0.0) * $factor, 1);

            $totalGrams += $grams;
            $totalCalories += $cal;
            $totalProtein += $prot;
            $totalCarbs += $carbs;
            $totalFat += $fat;
            $totalFiber += $fib;
            $totalSugar += $sug;
            $totalSatFat += $sat;
            $totalSodium += $sod;

            if ($food->source) {
                $sourcesUsed[$food->source->slug] = [
                    'name' => $food->source->name,
                    'version' => $food->source->version,
                    'license' => $food->source->license,
                    'url' => $food->source->url,
                ];
            }

            $ingredientBreakdown[] = [
                'food_id' => $food->id,
                'name' => $food->name_en,
                'name_fr' => $food->name_fr,
                'preparation_state' => $food->preparation_state,
                'input_quantity' => $qty,
                'input_unit' => $unit,
                'grams' => round($grams, 1),
                'calories' => $cal,
                'protein' => $prot,
                'carbs' => $carbs,
                'fat' => $fat,
                'fiber' => $fib,
                'sugar' => $sug,
                'saturated_fat' => $sat,
                'sodium' => $sod,
                'source' => $food->source?->name ?? 'Standard Reference',
            ];
        }

        // Whole recipe
        $wholeRecipe = [
            'total_weight_g' => round($totalGrams, 1),
            'calories' => round($totalCalories, 1),
            'protein' => round($totalProtein, 1),
            'carbs' => round($totalCarbs, 1),
            'fat' => round($totalFat, 1),
            'fiber' => round($totalFiber, 1),
            'sugar' => round($totalSugar, 1),
            'saturated_fat' => round($totalSatFat, 1),
            'sodium' => round($totalSodium, 1),
        ];

        // Per serving
        $perServing = [
            'servings' => $servings,
            'weight_g' => round($totalGrams / $servings, 1),
            'calories' => round($totalCalories / $servings, 1),
            'protein' => round($totalProtein / $servings, 1),
            'carbs' => round($totalCarbs / $servings, 1),
            'fat' => round($totalFat / $servings, 1),
            'fiber' => round($totalFiber / $servings, 1),
            'sugar' => round($totalSugar / $servings, 1),
            'saturated_fat' => round($totalSatFat / $servings, 1),
            'sodium' => round($totalSodium / $servings, 1),
        ];

        // Macro calorie ratios
        $macroEnergy = $this->calculateMacroPercentages($wholeRecipe['protein'], $wholeRecipe['carbs'], $wholeRecipe['fat']);

        return [
            'servings' => $servings,
            'per_serving' => $perServing,
            'whole_recipe' => $wholeRecipe,
            'macro_distribution' => $macroEnergy,
            'ingredients' => $ingredientBreakdown,
            'warnings' => $warnings,
            'sources' => array_values($sourcesUsed),
        ];
    }

    /**
     * Converts a given quantity and unit of food into grams.
     */
    public function convertToGrams(Food $food, float $quantity, string $unit): ?float
    {
        $u = strtolower(trim($unit));

        // Metric weight
        if ($u === 'g' || $u === 'gram' || $u === 'grams') {
            return $quantity;
        }
        if ($u === 'kg' || $u === 'kilogram' || $u === 'kilograms') {
            return $quantity * 1000.0;
        }

        // Imperial weight
        if ($u === 'oz' || $u === 'ounce' || $u === 'ounces') {
            return $quantity * 28.349523;
        }
        if ($u === 'lb' || $u === 'pound' || $u === 'pounds') {
            return $quantity * 453.59237;
        }

        // Piece / count
        if (in_array($u, ['piece', 'pieces', 'unit', 'units', 'item', 'items', 'slice', 'slices', 'clove', 'cloves'], true)) {
            if ($food->piece_weight_g !== null && $food->piece_weight_g > 0) {
                return $quantity * $food->piece_weight_g;
            }

            return null; // Cannot invent piece weight
        }

        // Cup
        if ($u === 'cup' || $u === 'cups') {
            if ($food->cup_weight_g !== null && $food->cup_weight_g > 0) {
                return $quantity * $food->cup_weight_g;
            }
            if ($food->density_g_per_ml !== null && $food->density_g_per_ml > 0) {
                return $quantity * 240.0 * $food->density_g_per_ml;
            }

            return null;
        }

        // Tablespoon
        if (in_array($u, ['tbsp', 'tablespoon', 'tablespoons', 'c. à soupe', 'c.a.s', 'cas'], true)) {
            if ($food->tbsp_weight_g !== null && $food->tbsp_weight_g > 0) {
                return $quantity * $food->tbsp_weight_g;
            }
            if ($food->density_g_per_ml !== null && $food->density_g_per_ml > 0) {
                return $quantity * 15.0 * $food->density_g_per_ml;
            }

            return null;
        }

        // Teaspoon
        if (in_array($u, ['tsp', 'teaspoon', 'teaspoons', 'c. à café', 'c.a.c', 'cac'], true)) {
            if ($food->tsp_weight_g !== null && $food->tsp_weight_g > 0) {
                return $quantity * $food->tsp_weight_g;
            }
            if ($food->density_g_per_ml !== null && $food->density_g_per_ml > 0) {
                return $quantity * 5.0 * $food->density_g_per_ml;
            }

            return null;
        }

        // Liquid volume (ml, l)
        if (in_array($u, ['ml', 'milliliter', 'milliliters', 'millilitre'], true)) {
            if ($food->density_g_per_ml !== null && $food->density_g_per_ml > 0) {
                return $quantity * $food->density_g_per_ml;
            }

            return null; // Density unknown: refuse to guess
        }

        if (in_array($u, ['l', 'liter', 'liters', 'litre', 'litres'], true)) {
            if ($food->density_g_per_ml !== null && $food->density_g_per_ml > 0) {
                return $quantity * 1000.0 * $food->density_g_per_ml;
            }

            return null;
        }

        return null;
    }

    /**
     * Calculates macronutrient energy distribution in kcal and percentages.
     *
     * @return array{protein_kcal: float, carbs_kcal: float, fat_kcal: float, total_macro_kcal: float, protein_pct: float, carbs_pct: float, fat_pct: float}
     */
    public function calculateMacroPercentages(float $proteinGrams, float $carbsGrams, float $fatGrams): array
    {
        $protKcal = $proteinGrams * self::KCAL_PER_G_PROTEIN;
        $carbsKcal = $carbsGrams * self::KCAL_PER_G_CARBS;
        $fatKcal = $fatGrams * self::KCAL_PER_G_FAT;
        $totalMacroKcal = $protKcal + $carbsKcal + $fatKcal;

        if ($totalMacroKcal <= 0) {
            return [
                'protein_kcal' => 0.0,
                'carbs_kcal' => 0.0,
                'fat_kcal' => 0.0,
                'total_macro_kcal' => 0.0,
                'protein_pct' => 0.0,
                'carbs_pct' => 0.0,
                'fat_pct' => 0.0,
            ];
        }

        return [
            'protein_kcal' => round($protKcal, 1),
            'carbs_kcal' => round($carbsKcal, 1),
            'fat_kcal' => round($fatKcal, 1),
            'total_macro_kcal' => round($totalMacroKcal, 1),
            'protein_pct' => round(($protKcal / $totalMacroKcal) * 100.0, 1),
            'carbs_pct' => round(($carbsKcal / $totalMacroKcal) * 100.0, 1),
            'fat_pct' => round(($fatKcal / $totalMacroKcal) * 100.0, 1),
        ];
    }
}
