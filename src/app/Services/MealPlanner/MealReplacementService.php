<?php

namespace App\Services\MealPlanner;

use App\DTO\MealPlanPreferences;
use Random\Engine\Mt19937;
use Random\Randomizer;

final class MealReplacementService
{
    public function __construct(
        private readonly RecipeFilterService $filter,
        private readonly MealPlanGenerator $generator,
    ) {}

    /**
     * Picks another recipe for one slot, leaving the rest of the plan untouched.
     *
     * @param  list<int>  $usedIds  recipes currently in the plan
     * @return array<string,mixed> recipe card
     *
     * @throws \DomainException
     */
    public function replace(MealPlanPreferences $prefs, string $meal, ?int $currentId, array $usedIds, int $seed): array
    {
        $prefs = new MealPlanPreferences($prefs->servings, $prefs->diet, $prefs->budget, [$meal], $prefs->days, $prefs->avoid, $prefs->have);
        $pool = $this->filter->pools($this->filter->profiles(), $prefs)[$meal] ?? [];

        return $this->generator->pick($pool, $meal, $prefs, $usedIds, new Randomizer(new Mt19937($seed)), $currentId)['card'];
    }
}
