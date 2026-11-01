<?php

namespace App\DTO;

final class MealPlanPreferences
{
    public const DIETS = ['none', 'vegetarian', 'vegan', 'high-protein', 'low-carb', 'gluten-free', 'dairy-free'];

    public const BUDGETS = ['budget', 'moderate', 'flexible'];

    public const MEALS = ['breakfast', 'lunch', 'dinner', 'snack'];

    /**
     * @param  list<string>  $meals
     * @param  list<string>  $avoid
     * @param  list<string>  $have
     */
    public function __construct(
        public readonly int $servings = 2,
        public readonly string $diet = 'none',
        public readonly string $budget = 'moderate',
        public readonly array $meals = ['breakfast', 'lunch', 'dinner'],
        public readonly int $days = 7,
        public readonly array $avoid = [],
        public readonly array $have = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $meals = array_values(array_intersect(self::MEALS, $data['meals'] ?? ['breakfast', 'lunch', 'dinner']));

        return new self(
            servings: (int) ($data['servings'] ?? 2),
            diet: (string) ($data['diet'] ?? 'none'),
            budget: (string) ($data['budget'] ?? 'moderate'),
            meals: $meals,
            days: (int) ($data['days'] ?? 7),
            avoid: self::terms($data['avoid'] ?? ''),
            have: self::terms($data['have'] ?? ''),
        );
    }

    /** @return list<string> */
    public static function terms(string|array|null $raw): array
    {
        $parts = is_array($raw) ? $raw : preg_split('/[,;\n]+/', (string) $raw);

        return collect($parts)
            ->map(fn ($t) => trim(mb_strtolower((string) $t)))
            ->filter(fn ($t) => mb_strlen($t) >= 2 && mb_strlen($t) <= 40)
            ->unique()->take(10)->values()->all();
    }
}
