<?php

namespace Tests\Feature;

use App\Services\IngredientConversionService;
use App\Services\IngredientSubstitutionService;
use Database\Seeders\IngredientSubstitutionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientSubstitutionTest extends TestCase
{
    use RefreshDatabase;

    private function seedCatalog(): void
    {
        $this->seed(IngredientSubstitutionSeeder::class);
    }

    private function search(array $data)
    {
        return $this->post(route('tools.ingredient-substitution.search'), $data + ['purpose' => 'general', 'diet' => 'none']);
    }

    public function test_index_has_seo_metadata_and_autocomplete(): void
    {
        $this->seedCatalog();
        $r = $this->get(route('tools.ingredient-substitution'))->assertOk();
        $r->assertSee('<title>Ingredient Substitution Tool – Find Easy Cooking Alternatives</title>', false);
        $r->assertSee('<link rel="canonical" href="'.route('tools.ingredient-substitution').'">', false);
        $r->assertSee('og:image', false);
        $r->assertSee('"@type":"WebApplication"', false);
        $r->assertSee('"@type":"BreadcrumbList"', false);
        $r->assertDontSee('FAQPage', false);
        $r->assertSee('<option value="Butter">', false);
        $r->assertDontSee('noindex');
    }

    public function test_search_shows_ranked_results_and_is_noindex(): void
    {
        $this->seedCatalog();
        $r = $this->search(['ingredient' => 'butter'])->assertOk();
        $r->assertSee('Best match');
        $r->assertSee('Coconut oil');
        $r->assertSee('noindex,follow', false);
        $r->assertSee('1 cup butter → 1 cup coconut oil');
    }

    public function test_alias_and_plural_match(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => 'Egg'])->assertSee('Flax egg');
        $this->search(['ingredient' => 'plain flour'])->assertSee('Gluten-free flour blend');
    }

    public function test_unknown_ingredient_is_explained_not_guessed(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => 'dragonfruit'])->assertOk()->assertSee('We don’t have', false)->assertDontSee('Best match');
    }

    public function test_purpose_filter(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => 'eggs', 'purpose' => 'cooking'])->assertSee('No suitable')->assertDontSee('Flax egg');
        $this->search(['ingredient' => 'butter', 'purpose' => 'desserts'])->assertDontSee('Vegetable or olive oil');
    }

    public function test_diet_filters_and_allergens(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => 'butter', 'diet' => 'vegan'])->assertDontSee('Plain Greek yogurt')->assertSee('Coconut oil');
        // Coconut oil is not tagged nut-free (coconut is listed as an allergen).
        $this->search(['ingredient' => 'butter', 'diet' => 'nut-free'])->assertDontSee('Coconut oil')->assertSee('Unsweetened applesauce');
        $this->search(['ingredient' => 'parmesan', 'diet' => 'egg-free'])->assertDontSee('Grana Padano');
        $this->search(['ingredient' => 'milk', 'diet' => 'none'])->assertSee('Contains: soy');
    }

    public function test_quantity_conversion_with_density(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => 'butter', 'quantity' => '100', 'unit' => 'g'])
            ->assertSee('7 tbsp (≈ 106 ml) coconut oil');
        $this->search(['ingredient' => 'eggs', 'quantity' => '2', 'unit' => 'item'])
            ->assertSee('2 tbsp (≈ 30 ml) ground flaxseed')->assertSee('6 tbsp (≈ 90 ml) water');
    }

    public function test_no_volume_to_weight_conversion_without_density(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => 'heavy cream', 'quantity' => '100', 'unit' => 'g'])
            ->assertSee('without a reliable density')->assertDontSee('For your quantity, use');
    }

    public function test_invalid_input_is_rejected(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => ''])->assertSessionHasErrors('ingredient');
        $this->search(['ingredient' => 'butter', 'purpose' => 'hack'])->assertSessionHasErrors('purpose');
        $this->search(['ingredient' => 'butter', 'quantity' => '-3', 'unit' => 'g'])->assertSessionHasErrors('quantity');
        $this->search(['ingredient' => 'butter', 'quantity' => '3'])->assertSessionHasErrors('unit');
    }

    public function test_input_is_escaped(): void
    {
        $this->seedCatalog();
        $this->search(['ingredient' => '<script>alert(1)</script>'])->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_form_carries_csrf_token(): void
    {
        $this->get(route('tools.ingredient-substitution'))->assertSee('name="_token"', false);
    }

    public function test_empty_database_is_handled(): void
    {
        $this->get(route('tools.ingredient-substitution'))->assertOk();
        $this->search(['ingredient' => 'butter'])->assertOk()->assertSee('We don’t have', false);
    }

    public function test_guides(): void
    {
        $this->seedCatalog();
        $r = $this->get(route('ingredient-guides.show', 'butter'))->assertOk();
        $r->assertSee('"@type":"Article"', false)->assertSee('Can I replace butter with oil in a cake?');
        $r->assertDontSee('FAQPage', false);
        $this->get(route('ingredient-guides.show', 'honey'))->assertNotFound();
        $this->get('/ingredient-substitutes/unknown')->assertNotFound();
    }

    public function test_sitemap_lists_tool_and_guides(): void
    {
        $this->seedCatalog();
        $this->get('/sitemap.xml')->assertSee(route('tools.ingredient-substitution'), false)
            ->assertSee(route('ingredient-guides.show', 'heavy-cream'), false);
    }

    public function test_conversion_service_formatting(): void
    {
        $c = new IngredientConversionService;
        $this->assertSame('½', $c->number(0.5));
        $this->assertSame('1 ¾', $c->number(1.75));
        $out = $c->scale([['name' => 'x', 'qty' => 1, 'unit' => 'cup', 'qty_max' => null]], 1, 'cup', 2, 'cup', null);
        $this->assertStringContainsString('2 cup', $out['lines'][0]);
        $this->assertSame(IngredientSubstitutionService::PURPOSES['general'], 'General use');
    }
}
