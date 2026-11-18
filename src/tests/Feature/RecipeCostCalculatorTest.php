<?php

namespace Tests\Feature;

use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeCostCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_page_has_seo_metadata_open_graph_and_local_tools(): void
    {
        $this->get(route('tools.recipe-cost-calculator'))
            ->assertOk()
            ->assertSee('<title>Free Recipe Cost Calculator – Cost Per Serving</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('tools.recipe-cost-calculator').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('assets/images/social/recipe-cost-calculator-og.jpg').'">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset('assets/images/social/recipe-cost-calculator-og.jpg').'">', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('recipe-cost-core.js', false)
            ->assertSee('recipe-cost-calculator.js', false)
            ->assertSee('Import JSON backup')
            ->assertSee('Compare two recipes')
            ->assertSee('No grocery prices are fetched');
    }

    public function test_calculator_route_is_in_sitemap_llms_and_primary_navigation(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.route('tools.recipe-cost-calculator').'</loc>', false);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee(route('tools.recipe-cost-calculator'));

        $this->get('/')
            ->assertOk()
            ->assertSee(route('tools.recipe-cost-calculator'));
    }

    public function test_recipe_page_offers_cost_calculator_with_safely_prefilled_ingredients(): void
    {
        $recipe = Recipe::create([
            'tasty_id' => 999998,
            'name' => 'Chicken and Oil',
            'num_servings' => 3,
            'sections' => [
                ['components' => [
                    ['raw_text' => '300 g chicken breast'],
                    ['raw_text' => '2 tbsp olive oil'],
                    ['raw_text' => '2 1/2 cups flour'],
                ]],
            ],
        ]);

        $response = $this->get(route('recipes.show', $recipe));
        $response->assertOk()
            ->assertSee('Calculate the cost of this recipe')
            ->assertSee(route('tools.recipe-cost-calculator'));

        preg_match('/<script type="application\/json" id="recipe-cost-page-preset">(.*?)<\/script>/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $preset = json_decode($matches[1], true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('Chicken and Oil', $preset['name']);
        $this->assertSame(3, $preset['servings']);
        $this->assertSame(['name' => 'chicken breast', 'quantity' => '300', 'unit' => 'g'], $preset['ingredients'][0]);
        $this->assertSame(['name' => 'olive oil', 'quantity' => '2', 'unit' => 'tbsp'], $preset['ingredients'][1]);
        $this->assertSame(['name' => '2 1/2 cups flour', 'quantity' => '', 'unit' => 'g'], $preset['ingredients'][2]);
    }
}
