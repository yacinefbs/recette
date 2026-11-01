<?php

namespace App\Services\MealPlanner;

use App\DTO\MealPlanPreferences;
use App\Models\Recipe;

/**
 * Classifies existing recipes (meal type, dietary compatibility, budget hint) from their
 * name, ingredients and nutrition, then filters them according to the user's preferences.
 * Dietary matching is keyword based: it is a guide, not a guarantee.
 */
final class RecipeFilterService
{
    private const NOT_A_MEAL = '/\b(cocktail|jackie-o|sangria|milkshake|hot chocolate|toddy|spritz|frappe|eggnog|tea party|seacuterie|pickle|chutney|salsa|stuffing)\b/u';

    private const BREAKFAST = '/\b(pancake|waffle|muffin|french toast|smoothie|parfait|banana bread|cinnamon roll|granola|oat|breakfast|biscuit|bagel)s?\b/u';

    private const SNACK = '/\b(cookie|brownie|cupcake|cake|pie|cheesecake|macaron|mousse|chewies|toffee|dip|chips|popcorn|nachos|crackers|strawberries|pudding|fries|bruschetta|hush puppies|snacks|snickerdoodles|pb&j)s?\b/u';

    private const MAIN_PROTEIN = '/\b(chicken|beef|shrimp|steak|pasta|sausage|salmon|pork)\b/u';

    private const MEAT = ['chicken', 'beef', 'pork', 'bacon', 'sausage', 'ham', 'turkey', 'lamb', 'steak', 'meatball', 'pepperoni', 'prosciutto', 'veal', 'duck', 'salami', 'brisket', 'filet mignon', 'anchovy', 'anchovies', 'fish', 'shrimp', 'salmon', 'tuna', 'halibut', 'cod', 'crab', 'lobster', 'scallop', 'clam', 'mussel', 'oyster', 'sardine', 'tilapia', 'prawn', 'pig', 'gelatin', 'lard'];

    private const ANIMAL = ['egg', 'milk', 'butter', 'cheese', 'cream', 'yogurt', 'yoghurt', 'honey', 'mayonnaise', 'ghee', 'whey', 'paneer', 'parmesan', 'mozzarella', 'cheddar', 'buttermilk', 'custard', 'burrata', 'ricotta', 'feta', 'half-and-half', 'marshmallow'];

    private const GLUTEN = ['flour', 'wheat', 'pasta', 'spaghetti', 'penne', 'lasagna', 'rotini', 'macaroni', 'noodle', 'ravioli', 'fettuccine', 'bread', 'breadcrumb', 'panko', 'tortilla', 'couscous', 'bulgur', 'barley', 'rye', 'cracker', 'biscuit', 'soy sauce', 'beer', 'bun', 'pita', 'naan', 'chapatti', 'bagel', 'cake mix', 'semolina', 'orzo', 'cereal', 'pretzel'];

    private const DAIRY = ['milk', 'butter', 'cheese', 'cream', 'yogurt', 'yoghurt', 'ghee', 'whey', 'paneer', 'buttermilk', 'custard', 'half-and-half', 'parmesan', 'mozzarella', 'cheddar', 'burrata', 'ricotta', 'feta', 'ice cream'];

    private const SAFE_PHRASES = ['peanut butter', 'almond butter', 'nut butter', 'cocoa butter', 'sunflower butter', 'coconut milk', 'almond milk', 'oat milk', 'soy milk', 'cashew milk', 'coconut cream', 'cream of tartar', 'almond flour', 'coconut flour', 'rice flour', 'chickpea flour', 'cassava flour', 'tapioca flour', 'buckwheat flour', 'gluten-free', 'gluten free', 'rice noodle', 'rice noodles', 'rice paper', 'vegan butter', 'vegan cheese', 'dairy-free', 'dairy free', 'plant-based', 'plant based', 'flax egg', 'egg replacer', 'nut-free', 'coconut yogurt', 'brown butter'];

    private const PREMIUM = '/\b(steak|filet|mignon|lamb|shrimp|salmon|halibut|lobster|brisket|prosciutto|burrata|scallop|crab|rack)\b/u';

    private const LIGHT = '/\b(salad|soup|bowl|taco|tacos|burrito|sandwich|wrap|fajita|fajitas|rice|grain|quick|easy|jacket potato)\b/u';

    private const HEARTY = '/\b(roast|bake|casserole|lasagna|stew|steak|pasta|gumbo|curry|chili|stroganoff|dinner|burger|alfredo)\b/u';

    /** @return array<int, array<string, mixed>> profiles keyed by recipe id */
    public function profiles(): array
    {
        $profiles = [];
        $recipes = Recipe::query()
            ->select('id', 'name', 'image_path', 'total_time_minutes', 'prep_time_minutes', 'cook_time_minutes', 'num_servings', 'nutrition', 'sections', 'instructions')
            ->get();

        foreach ($recipes as $recipe) {
            if ($profile = $this->profile($recipe)) {
                $profiles[$recipe->id] = $profile;
            }
        }

        return $profiles;
    }

    /** @return array<string, mixed>|null null when the recipe lacks the data needed to plan with it */
    public function profile(Recipe $recipe): ?array
    {
        $components = collect($recipe->sections ?? [])->flatMap(fn ($s) => $s['components'] ?? [])->values();
        if ($components->count() < 3 || empty($recipe->instructions) || (int) $recipe->num_servings < 1) {
            return null;
        }

        $name = mb_strtolower($recipe->name);
        $meals = $this->mealTypes($name);
        if ($meals === []) {
            return null;
        }

        $text = mb_strtolower($components->map(fn ($c) => ($c['ingredient']['name'] ?? '').' '.($c['raw_text'] ?? ''))->implode(' | '));
        $clean = str_replace(self::SAFE_PHRASES, ' ', $text.' | '.$name);
        $nutrition = $recipe->nutrition ?? [];

        return [
            'id' => $recipe->id,
            'name' => $recipe->name,
            'family' => str_contains($name, ':') ? trim(explode(':', $name)[0]) : 'r'.$recipe->id,
            'meals' => $meals,
            'text' => $text.' | '.$name,
            'meat' => self::has($clean, self::MEAT),
            'animal' => self::has($clean, self::ANIMAL),
            'gluten' => self::has($clean, self::GLUTEN),
            'dairy' => self::has($clean, self::DAIRY),
            'protein' => isset($nutrition['protein']) ? (float) $nutrition['protein'] : null,
            'carbs' => isset($nutrition['carbohydrates']) ? (float) $nutrition['carbohydrates'] : null,
            'cost' => $components->count() + 5 * preg_match_all(self::PREMIUM, $text),
            'light' => (bool) preg_match(self::LIGHT, $name),
            'hearty' => (bool) preg_match(self::HEARTY, $name),
            'card' => [
                'id' => $recipe->id,
                'name' => $recipe->name,
                'url' => route('recipes.show', $recipe->id),
                'image' => $recipe->image,
                'time' => $recipe->total_time_minutes ?: (($recipe->prep_time_minutes + $recipe->cook_time_minutes) ?: null),
                'servings' => (int) $recipe->num_servings,
            ],
        ];
    }

    /** @return list<string> */
    public function mealTypes(string $name): array
    {
        if (preg_match(self::NOT_A_MEAL, $name)) {
            return [];
        }
        if (preg_match(self::BREAKFAST, $name)) {
            return ['breakfast'];
        }
        if (preg_match(self::SNACK, $name) && ! preg_match(self::MAIN_PROTEIN, $name) || str_contains($name, 'nachos')) {
            return ['snack'];
        }

        return ['lunch', 'dinner'];
    }

    /**
     * @param  array<int, array<string, mixed>>  $profiles
     * @return array<string, list<array<string, mixed>>> pool per meal type
     */
    public function pools(array $profiles, MealPlanPreferences $prefs): array
    {
        $pools = array_fill_keys($prefs->meals, []);

        foreach ($profiles as $p) {
            if (! $this->matches($p, $prefs)) {
                continue;
            }
            foreach ($p['meals'] as $meal) {
                if (isset($pools[$meal])) {
                    $pools[$meal][] = $p;
                }
            }
        }

        return $pools;
    }

    public function matches(array $p, MealPlanPreferences $prefs): bool
    {
        $ok = match ($prefs->diet) {
            'vegetarian' => ! $p['meat'],
            'vegan' => ! $p['meat'] && ! $p['animal'],
            'gluten-free' => ! $p['gluten'],
            'dairy-free' => ! $p['dairy'],
            'high-protein' => $p['protein'] !== null && $p['protein'] >= 25,
            'low-carb' => $p['carbs'] !== null && $p['carbs'] <= 30,
            default => true,
        };

        if (! $ok) {
            return false;
        }

        foreach ($prefs->avoid as $term) {
            if (preg_match('/\b'.preg_quote($term, '/').'(?:e?s)?\b/u', $p['text'])) {
                return false;
            }
        }

        return true;
    }

    public static function countMatches(string $text, array $terms): int
    {
        return count(array_filter($terms, fn ($t) => preg_match('/\b'.preg_quote($t, '/').'(?:e?s)?\b/u', $text)));
    }

    private static function has(string $text, array $words): bool
    {
        return (bool) preg_match('/\b(?:'.implode('|', array_map(fn ($w) => preg_quote($w, '/'), $words)).')(?:e?s)?\b/u', $text);
    }
}
