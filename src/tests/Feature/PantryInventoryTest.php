<?php

namespace Tests\Feature;

use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantryInventoryTest extends TestCase
{
    use RefreshDatabase;

    private int $tastyId = 1000;

    public function test_pantry_page_has_seo_privacy_copy_and_tool_navigation(): void
    {
        $this->get(route('tools.pantry-inventory'))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.route('tools.pantry-inventory').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('assets/images/social/pantry-inventory-og.jpg').'">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset('assets/images/social/pantry-inventory-og.jpg').'">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('<meta property="og:image:height" content="630">', false)
            ->assertSee('Free Pantry Inventory Manager')
            ->assertSee('saved only in this browser on this device')
            ->assertSee('Recently Added')
            ->assertSee('Low Stock')
            ->assertSee('pantry-inventory-core.js', false)
            ->assertSee('Find Recipes With My Inventory')
            ->assertSee(route('tools.what'))
            ->assertSee(route('meal-planner.index'));

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.route('tools.pantry-inventory').'</loc>', false);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee(route('tools.pantry-inventory'));
    }

    public function test_recipe_matches_are_ranked_and_report_compatible_quantity_checks(): void
    {
        $recipe = $this->recipe('Chicken and Rice', [
            ['chicken breasts', '500', 'gram'],
            ['rice', '200', 'gram'],
            ['garlic', '2', 'clove'],
        ]);
        $expiryDate = now()->addDays(2)->toDateString();

        $response = $this->postJson(route('tools.pantry-inventory.recipes'), [
            'ingredients' => [
                ['name' => 'Chicken breast', 'quantity' => 100, 'unit' => 'g', 'expiry_date' => $expiryDate],
                ['name' => 'Rice', 'quantity' => 1, 'unit' => 'kg', 'expiry_date' => null],
            ],
            'prioritize_expiring' => true,
        ])->assertOk()
            ->assertJsonPath('recipes.0.recipe.name', $recipe->name)
            ->assertJsonPath('recipes.0.matched_count', 2)
            ->assertJsonPath('recipes.0.total_ingredients', 3)
            ->assertJsonPath('recipes.0.matched.0.quantity_sufficient', false)
            ->assertJsonPath('recipes.0.matched.0.expiring_soon', true)
            ->assertJsonPath('recipes.0.matched.1.quantity_sufficient', true);

        $this->assertSame('2 clove garlic', $response->json('recipes.0.missing.0'));
    }

    public function test_expiring_ingredients_can_take_priority_over_a_higher_match_ratio(): void
    {
        $bestOverallMatch = $this->recipe('Chicken Rice Tomato', [
            ['chicken', '100', 'gram'],
            ['rice', '100', 'gram'],
            ['tomato', '1', 'piece'],
        ]);
        $bestExpiringMatch = $this->recipe('Chicken Garlic Egg', [
            ['chicken', '100', 'gram'],
            ['garlic', '2', 'clove'],
            ['egg', '1', 'piece'],
        ]);
        $expiryDate = now()->addDays(2)->toDateString();
        $ingredients = [
            ['name' => 'Chicken', 'quantity' => 100, 'unit' => 'g', 'expiry_date' => $expiryDate],
            ['name' => 'Rice', 'quantity' => 200, 'unit' => 'g', 'expiry_date' => null],
            ['name' => 'Tomato', 'quantity' => 1, 'unit' => 'pieces', 'expiry_date' => null],
            ['name' => 'Garlic', 'quantity' => 2, 'unit' => 'pieces', 'expiry_date' => $expiryDate],
        ];

        $this->postJson(route('tools.pantry-inventory.recipes'), [
            'ingredients' => $ingredients,
            'prioritize_expiring' => false,
        ])->assertOk()->assertJsonPath('recipes.0.recipe.id', $bestOverallMatch->id);

        $this->postJson(route('tools.pantry-inventory.recipes'), [
            'ingredients' => $ingredients,
            'prioritize_expiring' => true,
        ])->assertOk()
            ->assertJsonPath('recipes.0.recipe.id', $bestExpiringMatch->id)
            ->assertJsonPath('recipes.0.matched.1.expiring_soon', true);
    }

    public function test_recipe_matching_rejects_invalid_or_excessive_inventory_payloads(): void
    {
        $this->postJson(route('tools.pantry-inventory.recipes'), ['ingredients' => []])
            ->assertUnprocessable();

        $this->postJson(route('tools.pantry-inventory.recipes'), [
            'ingredients' => array_fill(0, 31, ['name' => 'Rice', 'quantity' => 1, 'unit' => 'kg']),
        ])->assertUnprocessable();
    }

    public function test_sitemap_includes_public_pages_and_tools_but_not_private_actions(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->content();
        $publicRoutes = [
            route('home'),
            route('recipes.index'),
            route('meal-planner.index'),
            route('cooking-tips.index'),
            route('tools.cooking-timer'),
            route('tools.pantry-inventory'),
            route('tools.converter'),
            route('tools.nutrition-calculator'),
            route('tools.what'),
            route('tools.ingredient-substitution'),
            route('categories.show', 'breakfast'),
            route('pages.show', 'about'),
            route('pages.show', 'privacy-policy'),
        ];

        foreach ($publicRoutes as $url) {
            $this->assertStringContainsString('<loc>'.$url.'</loc>', $sitemap);
        }

        foreach (array_keys(\App\Http\Controllers\PageController::PAGES) as $slug) {
            $this->assertStringContainsString('<loc>'.route('pages.show', $slug).'</loc>', $sitemap);
        }
        foreach (array_keys(\App\Support\Categories::ALL) as $slug) {
            $this->assertStringContainsString('<loc>'.route('categories.show', $slug).'</loc>', $sitemap);
        }

        $this->assertStringNotContainsString(route('tools.pantry-inventory.recipes'), $sitemap);
        $this->assertStringNotContainsString(route('meal-planner.generate'), $sitemap);
        $this->assertStringNotContainsString(route('social.login'), $sitemap);
    }

    /** @param array<int, array{0:string,1:string,2:string}> $ingredients */
    private function recipe(string $name, array $ingredients): Recipe
    {
        $components = [];
        foreach ($ingredients as [$ingredient, $quantity, $unit]) {
            $components[] = [
                'raw_text' => $quantity.' '.$unit.' '.$ingredient,
                'ingredient' => ['name' => $ingredient, 'display_singular' => $ingredient],
                'measurements' => [['quantity' => $quantity, 'unit' => ['name' => $unit]]],
            ];
        }

        return Recipe::create([
            'tasty_id' => $this->tastyId++,
            'name' => $name,
            'sections' => [['components' => $components]],
            'instructions' => [['display_text' => 'Cook until done.']],
        ]);
    }
}
