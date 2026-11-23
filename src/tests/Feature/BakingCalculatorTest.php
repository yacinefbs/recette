<?php

namespace Tests\Feature;

use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BakingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_baking_calculator_has_seo_schema_and_og_image(): void
    {
        $response = $this->get(route('tools.baking-calculator'))
            ->assertOk()
            ->assertSee('<title>Baking Calculator – Bread &amp; Pizza Dough Ingredient Calculator</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('tools.baking-calculator').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('assets/images/social/baking-calculator-og.jpg').'">', false)
            ->assertSee('<meta name="twitter:image" content="'.asset('assets/images/social/baking-calculator-og.jpg').'">', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('baking-calculator-core.js', false)
            ->assertSee('baking-calculator.js', false)
            ->assertSee('Use sourdough starter')
            ->assertSee('Baker’s Percentage')
            ->assertSee('Cooking Converter')
            ->assertSee('Recipe Cost Calculator')
            ->assertSee('Nutrition Calculator')
            ->assertSee('Ingredient Substitution Tool');

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $this->assertFileExists(public_path('assets/images/social/baking-calculator-og.jpg'));
    }

    public function test_baking_calculator_is_in_sitemap_llms_navigation_and_homepage(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.route('tools.baking-calculator').'</loc>', false);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee(route('tools.baking-calculator'));

        $this->get('/')
            ->assertOk()
            ->assertSee(route('tools.baking-calculator'));

        $this->get(route('tools.converter'))
            ->assertOk()
            ->assertSee(route('tools.baking-calculator'));
    }

    public function test_bread_and_pizza_recipe_pages_link_to_the_calculator(): void
    {
        $recipe = Recipe::create([
            'tasty_id' => 999997,
            'name' => 'Homemade Pizza Dough',
        ]);

        $this->get(route('recipes.show', $recipe))
            ->assertOk()
            ->assertSee(route('tools.baking-calculator'))
            ->assertSee('Open the Baking Calculator');
    }
}
