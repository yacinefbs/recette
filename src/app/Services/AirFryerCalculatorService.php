<?php

namespace App\Services;

use InvalidArgumentException;

class AirFryerCalculatorService
{
    private const SAFETY_GUIDANCE = [
        'poultry' => ['temperature_f' => 165, 'label' => 'Poultry.'],
        'ground-meat' => ['temperature_f' => 160, 'alternate_temperature_f' => 165, 'label' => 'Ground meat. Safe minimums depend on meat type.'],
        'whole-cut' => ['temperature_f' => 145, 'label' => 'Whole cuts of beef, pork, veal, and lamb; rest for 3 minutes.'],
        'seafood' => ['temperature_f' => 145, 'label' => 'Fish and shellfish.'],
    ];

    public function foods(): array
    {
        $foods = [];
        foreach (config('air_fryer.foods', []) as $setting) {
            $slug = $setting['slug'];
            if (! isset($foods[$slug])) {
                $foods[$slug] = [
                    'slug' => $slug,
                    'name' => $setting['name'],
                    'category' => $setting['category'],
                    'settings' => [],
                    'size_options' => [],
                ];
            }

            $foods[$slug]['settings'][] = $setting;
            if (! empty($setting['size'])) {
                $foods[$slug]['size_options'][] = $setting['size'];
            }
        }

        foreach ($foods as &$food) {
            $food['size_options'] = array_values(array_unique($food['size_options']));
        }
        unset($food);

        return array_values($foods);
    }

    public function calculate(string $foodSlug, string $condition, ?string $size, int $quantityGrams, string $preheat, string $unit): array
    {
        $food = collect($this->foods())->firstWhere('slug', $foodSlug);
        $setting = $food ? collect($food['settings'])->first(function (array $candidate) use ($condition, $size) {
            return $candidate['condition'] === $condition
                && (($candidate['size'] ?? null) === $size);
        }) : null;

        if (! $food || ! $setting) {
            throw new InvalidArgumentException('No sourced cooking setting exists for that food, condition, and size.');
        }

        $safety = isset($setting['safety']) ? self::SAFETY_GUIDANCE[$setting['safety']] : null;
        $temperature = $unit === 'c'
            ? (int) round(($setting['temperature_f'] - 32) * 5 / 9)
            : $setting['temperature_f'];
        $safetyTemperature = $safety && $unit === 'c'
            ? (int) round(($safety['temperature_f'] - 32) * 5 / 9)
            : ($safety['temperature_f'] ?? null);

        $warnings = [
            'Quantity does not change this published time range. Avoid overcrowding the basket; cook in batches if needed.',
            $preheat === 'no'
                ? 'This source does not provide a separate no-preheat adjustment. Check food early and follow your appliance instructions.'
                : 'These are starting settings, not a guarantee; check food as it cooks and follow your appliance instructions.',
        ];
        if (isset($setting['source_portion'])) {
            $warnings[] = 'The published chart describes a '.$setting['source_portion'].' portion; it does not provide a quantity-based time adjustment.';
        }

        return [
            'food' => ['slug' => $food['slug'], 'name' => $food['name'], 'category' => $food['category']],
            'condition' => $condition,
            'size' => $setting['size'] ?? null,
            'quantity_grams' => $quantityGrams,
            'preheat' => $preheat,
            'temperature' => $temperature,
            'temperature_c' => (int) round(($setting['temperature_f'] - 32) * 5 / 9),
            'temperature_f' => $setting['temperature_f'],
            'unit' => $unit,
            'time_min' => $setting['time_min'],
            'time_max' => $setting['time_max'],
            'timer_minutes' => (int) ceil(($setting['time_min'] + $setting['time_max']) / 2),
            'source_portion' => $setting['source_portion'] ?? null,
            'tips' => $this->tipsFor($food['category'], $setting),
            'safety' => $safety ? [
                'message' => $safety['label'].' Recommended internal temperature: '.$safetyTemperature.'°'.strtoupper($unit).'.',
                'temperature' => $safetyTemperature,
                'temperature_c' => (int) round(($safety['temperature_f'] - 32) * 5 / 9),
                'temperature_f' => $safety['temperature_f'],
                'alternate_temperature' => isset($safety['alternate_temperature_f'])
                    ? ($unit === 'c' ? (int) round(($safety['alternate_temperature_f'] - 32) * 5 / 9) : $safety['alternate_temperature_f'])
                    : null,
                'alternate_temperature_c' => isset($safety['alternate_temperature_f'])
                    ? (int) round(($safety['alternate_temperature_f'] - 32) * 5 / 9)
                    : null,
                'alternate_temperature_f' => $safety['alternate_temperature_f'] ?? null,
                'unit' => $unit,
                'source_name' => config('air_fryer.safety_source.name'),
                'source_url' => config('air_fryer.safety_source.url'),
            ] : null,
            'warnings' => $warnings,
            'source' => [
                'name' => config('air_fryer.source.name'),
                'url' => config('air_fryer.source.url'),
                'verified_on' => config('air_fryer.source.verified_on'),
            ],
        ];
    }

    private function tipsFor(string $category, array $setting): array
    {
        $tips = [
            'Spread food in a single layer and avoid overcrowding the basket.',
            'Turn or shake the food at least once if appropriate for the food and your appliance.',
        ];

        if ($category === 'Vegetables') {
            array_unshift($tips, 'Cut pieces to a similar size so they cook more evenly.');
        } elseif (in_array($category, ['Meat & Poultry', 'Fish & Seafood'], true) || isset($setting['safety'])) {
            array_unshift($tips, 'For meat, poultry, or seafood, verify doneness with a food thermometer; time alone cannot confirm safety.');
        }

        return $tips;
    }
}
