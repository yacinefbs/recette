<?php

namespace Tests\Feature\MealPlanner;

use App\DTO\MealPlanPreferences;
use App\Models\Recipe;
use App\Services\MealPlanner\IngredientNormalizer;
use App\Services\MealPlanner\ShoppingListService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealPlannerTest extends TestCase
{
    use RefreshDatabase;

    private int $tastyId = 1;

    /** @param array<int, array{0:string,1:string,2:string}> $ingredients [name, quantity, unit] */
    private function recipe(string $name, array $ingredients, int $servings = 4, array $nutrition = []): Recipe
    {
        $components = [];
        foreach ($ingredients as $i => [$ing, $qty, $unit]) {
            $components[] = [
                'raw_text' => "$qty $unit $ing",
                'ingredient' => ['id' => crc32($ing), 'name' => $ing, 'display_singular' => $ing],
                'measurements' => $qty === '' ? [] : [['quantity' => $qty, 'unit' => ['name' => $unit]]],
            ];
        }

        return Recipe::create([
            'tasty_id' => $this->tastyId++,
            'name' => $name,
            'num_servings' => $servings,
            'total_time_minutes' => 30,
            'nutrition' => $nutrition,
            'sections' => [['components' => $components]],
            'instructions' => [['display_text' => 'Cook it.']],
        ]);
    }

    private function seedCatalogue(): void
    {
        $base = [['salt', '1', 'teaspoon'], ['oil', '2', 'tablespoon'], ['onion', '1', '']];
        foreach (['Fluffy Pancakes', 'Berry Waffles', 'Banana Muffins', 'Oat Parfait', 'Apple Smoothie Bowl'] as $n) {
            $this->recipe($n, array_merge($base, [['flour', '200', 'gram']]));
        }
        foreach (['Roast Chicken Dinner', 'Beef Stew', 'Shrimp Scampi', 'Pork Chops'] as $n) {
            $this->recipe($n, array_merge($base, [[strtolower(explode(' ', $n)[0]), '400', 'gram']]), 4, ['protein' => 40, 'carbohydrates' => 10]);
        }
        foreach (['Tomato Soup', 'Lentil Curry', 'Veggie Tacos', 'Greek Salad', 'Mushroom Risotto', 'Cheese Pizza Bake', 'Bean Chili', 'Vegan Buddha Bowl'] as $n) {
            $extra = str_contains($n, 'Cheese') ? [['cheddar cheese', '100', 'gram']] : [['tomato', '3', '']];
            $this->recipe($n, array_merge($base, $extra), 4, ['protein' => 10, 'carbohydrates' => 60]);
        }
        foreach (['Chocolate Cookies', 'Fudgy Brownies', 'Lemon Cake'] as $n) {
            $this->recipe($n, array_merge($base, [['sugar', '100', 'gram']]));
        }
    }

    private function payload(array $over = []): array
    {
        return array_merge(['servings' => 2, 'diet' => 'none', 'budget' => 'moderate', 'meals' => ['breakfast', 'lunch', 'dinner'], 'days' => 7, 'seed' => 42], $over);
    }

    public function test_page_is_indexable_and_has_h1(): void
    {
        $this->get('/weekly-meal-planner')->assertOk()
            ->assertSee('Free Weekly Meal Planner', false)
            ->assertSee('<link rel="canonical" href="'.route('meal-planner.index').'">', false)
            ->assertDontSee('noindex');
    }

    public function test_generates_a_seven_day_plan_with_three_meals(): void
    {
        $this->seedCatalogue();
        $res = $this->postJson('/weekly-meal-planner/generate', $this->payload())->assertOk();
        $slots = $res->json('slots');

        $this->assertCount(21, $slots);
        $this->assertSame(range(1, 7), array_values(array_unique(array_column($slots, 'day'))));
        foreach ($slots as $s) {
            $this->assertNotEmpty($s['recipe']['name']);
            $this->assertArrayHasKey('url', $s['recipe']);
        }
    }

    public function test_same_seed_gives_same_plan(): void
    {
        $this->seedCatalogue();
        $a = $this->postJson('/weekly-meal-planner/generate', $this->payload())->json('slots');
        $b = $this->postJson('/weekly-meal-planner/generate', $this->payload())->json('slots');
        $this->assertSame($a, $b);
    }

    public function test_only_lunch_and_dinner(): void
    {
        $this->seedCatalogue();
        $slots = $this->postJson('/weekly-meal-planner/generate', $this->payload(['meals' => ['lunch', 'dinner'], 'days' => 5]))->assertOk()->json('slots');

        $this->assertCount(10, $slots);
        $this->assertEmpty(array_filter($slots, fn ($s) => $s['meal'] === 'breakfast'));
        $this->assertEmpty(array_filter($slots, fn ($s) => str_contains($s['recipe']['name'], 'Pancakes')));
    }

    public function test_meal_types_are_respected(): void
    {
        $filter = app(\App\Services\MealPlanner\RecipeFilterService::class);
        $this->assertSame(['breakfast'], $filter->mealTypes('fluffy pancakes'));
        $this->assertSame(['snack'], $filter->mealTypes('fudgy brownies'));
        $this->assertSame(['lunch', 'dinner'], $filter->mealTypes('creamy chicken pasta'));
        $this->assertSame([], $filter->mealTypes('jackie-o cocktail: the classic'));
    }

    public function test_vegetarian_and_vegan_filters(): void
    {
        $this->seedCatalogue();
        $veg = $this->postJson('/weekly-meal-planner/generate', $this->payload(['diet' => 'vegetarian', 'meals' => ['dinner']]))->assertOk()->json('slots');
        foreach ($veg as $s) {
            $this->assertNotContains($s['recipe']['name'], ['Roast Chicken Dinner', 'Beef Stew', 'Shrimp Scampi', 'Pork Chops']);
        }

        $vegan = $this->postJson('/weekly-meal-planner/generate', $this->payload(['diet' => 'vegan', 'meals' => ['dinner']]))->assertOk()->json('slots');
        foreach ($vegan as $s) {
            $this->assertNotContains($s['recipe']['name'], ['Cheese Pizza Bake', 'Roast Chicken Dinner']);
        }
    }

    public function test_high_protein_and_low_carb_use_nutrition(): void
    {
        $this->seedCatalogue();
        $slots = $this->postJson('/weekly-meal-planner/generate', $this->payload(['diet' => 'high-protein', 'meals' => ['dinner']]))->assertOk()->json('slots');
        foreach ($slots as $s) {
            $this->assertContains($s['recipe']['name'], ['Roast Chicken Dinner', 'Beef Stew', 'Shrimp Scampi', 'Pork Chops']);
        }
    }

    public function test_forbidden_ingredients_are_excluded(): void
    {
        $this->seedCatalogue();
        $slots = $this->postJson('/weekly-meal-planner/generate', $this->payload(['meals' => ['dinner'], 'avoid' => 'chicken, tomato']))->assertOk()->json('slots');
        foreach ($slots as $s) {
            $this->assertNotContains($s['recipe']['name'], ['Roast Chicken Dinner', 'Tomato Soup', 'Veggie Tacos', 'Greek Salad']);
        }
    }

    public function test_replace_changes_only_the_requested_slot_with_same_meal_type(): void
    {
        $this->seedCatalogue();
        $plan = $this->postJson('/weekly-meal-planner/generate', $this->payload(['meals' => ['dinner'], 'days' => 3]))->json('slots');
        $current = $plan[1]['recipe'];

        $res = $this->postJson('/weekly-meal-planner/replace-meal', $this->payload([
            'meals' => ['dinner'], 'days' => 3, 'meal' => 'dinner', 'current_id' => $current['id'],
            'used_ids' => array_column(array_column($plan, 'recipe'), 'id'),
        ]))->assertOk();

        $new = $res->json('recipe');
        $this->assertNotSame($current['id'], $new['id']);
        $this->assertNotContains($new['name'], ['Fluffy Pancakes', 'Chocolate Cookies']);
    }

    public function test_locked_meals_survive_regeneration(): void
    {
        $this->seedCatalogue();
        $plan = $this->postJson('/weekly-meal-planner/generate', $this->payload())->json('slots');
        $lock = $plan[4];

        foreach ([7, 8, 9] as $seed) {
            $slots = $this->postJson('/weekly-meal-planner/generate', $this->payload([
                'seed' => $seed,
                'locked' => [['day' => $lock['day'], 'meal' => $lock['meal'], 'recipe_id' => $lock['recipe']['id']]],
            ]))->assertOk()->json('slots');
            $same = collect($slots)->first(fn ($s) => $s['day'] === $lock['day'] && $s['meal'] === $lock['meal']);
            $this->assertSame($lock['recipe']['id'], $same['recipe']['id']);
            $this->assertTrue($same['locked']);
        }
    }

    public function test_empty_catalogue_and_too_strict_filters_return_a_message(): void
    {
        $this->postJson('/weekly-meal-planner/generate', $this->payload())->assertStatus(422)->assertJsonStructure(['message']);

        $this->seedCatalogue();
        $this->postJson('/weekly-meal-planner/generate', $this->payload(['avoid' => 'salt']))->assertStatus(422)->assertJsonStructure(['message']);
    }

    public function test_invalid_configuration_is_rejected(): void
    {
        $this->seedCatalogue();
        foreach ([['servings' => 0], ['servings' => 99], ['diet' => 'carnivore'], ['days' => 4], ['meals' => []], ['meals' => ['brunch']], ['avoid' => str_repeat('a', 400)]] as $bad) {
            $this->postJson('/weekly-meal-planner/generate', $this->payload($bad))->assertStatus(422);
        }
    }

    public function test_quantity_parsing(): void
    {
        $this->assertEqualsWithDelta(1.5, IngredientNormalizer::parseQuantity('1 ½'), 0.001);
        $this->assertEqualsWithDelta(0.75, IngredientNormalizer::parseQuantity('¾'), 0.001);
        $this->assertEqualsWithDelta(2.5, IngredientNormalizer::parseQuantity('2.5'), 0.001);
        $this->assertEqualsWithDelta(1.5, IngredientNormalizer::parseQuantity('1 1/2'), 0.001);
        $this->assertSame(0.0, IngredientNormalizer::parseQuantity('0'));
        $this->assertSame(0.0, IngredientNormalizer::parseQuantity(''));
    }

    private function flat(array $groups): array
    {
        $out = [];
        foreach ($groups as $g) {
            foreach ($g['items'] as $i) {
                $out[$i['name']] = $i['quantity'];
            }
        }

        return $out;
    }

    public function test_shopping_list_scales_aggregates_and_converts(): void
    {
        $a = $this->recipe('Chicken A', [['chicken', '200', 'gram'], ['milk', '250', 'milliliter'], ['egg', '2', ''], ['butter', '8', 'ounce']], 2);
        $b = $this->recipe('Chicken B', [['chicken', '0.3', 'kilogram'], ['milk', '1', 'liter'], ['egg', '1', ''], ['butter', '1', 'cup']], 2);

        $list = $this->flat(app(ShoppingListService::class)->build([$a->id, $b->id], 4));

        $this->assertSame('1 kg', $list['Chicken']);          // (200 + 300) * 2
        $this->assertStringStartsWith('2.5 l', $list['Milk']); // (250 + 1000) * 2 ml
        $this->assertSame('6 pcs', $list['Egg']);                  // (2 + 1) * 2
        $this->assertStringContainsString('g', $list['Butter']); // ounce converted to grams, cup stays a separate volume part
        $this->assertStringContainsString(' + ', $list['Butter']);
        $this->assertStringContainsString('ml', $list['Butter']);
    }

    public function test_shopping_list_repeats_and_missing_quantities(): void
    {
        $r = $this->recipe('Salted Dish', [['pasta', '100', 'gram'], ['salt', '', ''], ['olive oil', '1', 'tablespoon']], 2);
        $groups = app(ShoppingListService::class)->build([$r->id, $r->id], 2);
        $list = $this->flat($groups);

        $this->assertSame('200 g', $list['Pasta']);
        $this->assertSame('', $list['Salt']); // listed by name only, no invented amount
        $this->assertCount(1, array_filter(array_keys($list), fn ($n) => $n === 'Pasta'));
    }

    public function test_shopping_list_endpoint_validates_and_groups(): void
    {
        $r = $this->recipe('Chicken Rice', [['chicken breast', '500', 'gram'], ['rice', '200', 'gram'], ['tomato', '2', ''], ['egg', '1', '']], 2);

        $res = $this->postJson('/weekly-meal-planner/shopping-list', ['servings' => 2, 'recipe_ids' => [$r->id]])->assertOk();
        $cats = array_column($res->json('groups'), 'category');
        $this->assertContains('Meat & Fish', $cats);
        $this->assertContains('Dairy & Eggs', $cats);

        $this->postJson('/weekly-meal-planner/shopping-list', ['servings' => 2, 'recipe_ids' => []])->assertStatus(422);
        $this->postJson('/weekly-meal-planner/shopping-list', ['servings' => 'x', 'recipe_ids' => [1]])->assertStatus(422);
    }

    public function test_preferences_dto_parses_terms(): void
    {
        $p = MealPlanPreferences::fromArray(['avoid' => ' Peanuts, ,Mushrooms;a ', 'meals' => ['dinner', 'bogus']]);
        $this->assertSame(['peanuts', 'mushrooms'], $p->avoid);
        $this->assertSame(['dinner'], $p->meals);
    }
}
