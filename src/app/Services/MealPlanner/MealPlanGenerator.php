<?php

namespace App\Services\MealPlanner;

use App\DTO\MealPlanPreferences;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Deterministic meal picking from the existing catalogue: same preferences + same seed = same plan.
 */
final class MealPlanGenerator
{
    public function __construct(private readonly RecipeFilterService $filter) {}

    /**
     * @param  list<array{day:int,meal:string,recipe_id:int}>  $locked
     * @return array{slots: list<array<string,mixed>>, notes: list<string>}
     *
     * @throws \DomainException when the catalogue cannot support the requested plan
     */
    public function generate(MealPlanPreferences $prefs, array $locked = [], int $seed = 1): array
    {
        $profiles = $this->filter->profiles();
        if ($profiles === []) {
            throw new \DomainException('There are not enough recipes available to build a meal plan yet.');
        }

        $pools = $this->filter->pools($profiles, $prefs);
        $notes = [];
        foreach ($pools as $meal => $pool) {
            if (count($pool) < 2) {
                throw new \DomainException(sprintf('Only %d recipe(s) match your preferences for %s. Try another diet, remove an ingredient to avoid, or choose different meals.', count($pool), $meal));
            }
            if (count($pool) < $prefs->days) {
                $notes[] = sprintf('Only %d %s recipes match your preferences, so some will repeat.', count($pool), $meal);
            }
        }

        $random = new Randomizer(new Mt19937($seed));
        $lockedMap = [];
        foreach ($locked as $l) {
            if (isset($profiles[$l['recipe_id']]) && $l['day'] >= 1 && $l['day'] <= $prefs->days && in_array($l['meal'], $prefs->meals, true)) {
                $lockedMap[$l['day'].'-'.$l['meal']] = $profiles[$l['recipe_id']];
            }
        }

        $used = array_map(fn ($p) => $p['id'], array_values($lockedMap));
        $slots = [];
        for ($day = 1; $day <= $prefs->days; $day++) {
            foreach ($prefs->meals as $meal) {
                $key = $day.'-'.$meal;
                if (isset($lockedMap[$key])) {
                    $slots[] = ['day' => $day, 'meal' => $meal, 'recipe' => $lockedMap[$key]['card'], 'locked' => true];

                    continue;
                }
                $pick = $this->pick($pools[$meal], $meal, $prefs, $used, $random);
                $used[] = $pick['id'];
                $slots[] = ['day' => $day, 'meal' => $meal, 'recipe' => $pick['card'], 'locked' => false];
            }
        }

        return ['slots' => $slots, 'notes' => $notes];
    }

    /**
     * @param  list<array<string,mixed>>  $pool
     * @param  list<int>  $used  recipe ids already placed in the plan (repeats allowed)
     * @return array<string,mixed>
     */
    public function pick(array $pool, string $meal, MealPlanPreferences $prefs, array $used, Randomizer $random, ?int $excludeId = null): array
    {
        $counts = array_count_values($used);
        $usedFamilies = [];
        foreach ($pool as $p) {
            if (isset($counts[$p['id']])) {
                $usedFamilies[$p['family']] = true;
            }
        }

        $weight = ['budget' => 0.4, 'moderate' => 0.1, 'flexible' => 0.0][$prefs->budget] ?? 0.1;
        $best = null;
        $bestScore = -INF;
        foreach ($pool as $p) {
            if ($p['id'] === $excludeId) {
                continue;
            }
            $score = -$weight * $p['cost']
                + 4 * RecipeFilterService::countMatches($p['text'], $prefs->have)
                - 10 * ($counts[$p['id']] ?? 0)
                - (isset($usedFamilies[$p['family']]) ? 6 : 0)
                + ($meal === 'lunch' && $p['light'] ? 2 : 0)
                + ($meal === 'dinner' && $p['hearty'] ? 2 : 0)
                + $random->getInt(0, 600) / 100;
            if ($score > $bestScore) {
                $best = $p;
                $bestScore = $score;
            }
        }

        if ($best === null) {
            throw new \DomainException('No other recipe matches your preferences for this meal.');
        }

        return $best;
    }
}
