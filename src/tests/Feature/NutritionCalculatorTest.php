<?php

namespace Tests\Feature;

use App\Models\Food;
use App\Models\NutritionSource;
use App\Models\Recipe;
use App\Services\Nutrition\NutritionCalculatorService;
use Database\Seeders\NutritionDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(NutritionDatabaseSeeder::class);
    }

    public function test_index_page_loads_and_has_seo_and_schema(): void
    {
        $response = $this->get(route('tools.nutrition-calculator'));

        $response->assertOk();
        $response->assertSee('Free Nutrition Calculator', false);
        $response->assertSee('<link rel="canonical" href="'.route('tools.nutrition-calculator').'">', false);
        $response->assertSee('"@type":"WebApplication"', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertSee('How to Calculate Calories in a Recipe', false);
    }

    public function test_food_search_works_in_english_and_french(): void
    {
        // English query
        $enResponse = $this->getJson(route('tools.nutrition-calculator.search', ['q' => 'chicken']));
        $enResponse->assertOk();
        $enResponse->assertJsonFragment(['name' => 'Chicken breast (raw)']);

        // French query
        $frResponse = $this->getJson(route('tools.nutrition-calculator.search', ['q' => 'blanc de poulet']));
        $frResponse->assertOk();
        $frResponse->assertJsonFragment(['name_fr' => 'Blanc de poulet (cru)']);

        // Rice in French
        $riceResponse = $this->getJson(route('tools.nutrition-calculator.search', ['q' => 'riz']));
        $riceResponse->assertOk();
        $riceResponse->assertJsonFragment(['name_fr' => 'Riz blanc (cru)']);
    }

    public function test_calculation_with_user_sample_chicken_rice_oil(): void
    {
        $chicken = Food::where('source_food_id', 'FDC-171077')->firstOrFail();
        $rice = Food::where('source_food_id', 'FDC-169756')->firstOrFail();
        $oil = Food::where('source_food_id', 'CIQUAL-17440')->firstOrFail();

        $payload = [
            'servings' => 2,
            'ingredients' => [
                ['food_id' => $chicken->id, 'quantity' => 200, 'unit' => 'g'],
                ['food_id' => $rice->id, 'quantity' => 150, 'unit' => 'g'],
                ['food_id' => $oil->id, 'quantity' => 10, 'unit' => 'g'],
            ],
        ];

        $response = $this->postJson(route('tools.nutrition-calculator.calculate'), $payload);

        $response->assertOk();
        $data = $response->json();

        // 200g chicken (120*2=240 kcal) + 150g rice (365*1.5=547.5 kcal) + 10g oil (884*0.1=88.4 kcal) = 875.9 kcal
        $this->assertEquals(875.9, $data['whole_recipe']['calories']);
        // Per serving (divided by 2): 438.0 kcal
        $this->assertEquals(438.0, $data['per_serving']['calories']);

        // Protein: chicken (22.5*2=45.0g) + rice (round(7.1*1.5=10.65, 1)=10.6g) = 55.6g total -> 27.8g per serving
        $this->assertEquals(55.6, $data['whole_recipe']['protein']);
        $this->assertEquals(27.8, $data['per_serving']['protein']);

        // Carbs: rice (80*1.5=120g) = 120g total -> 60.0g per serving
        $this->assertEquals(120.0, $data['whole_recipe']['carbs']);
        $this->assertEquals(60.0, $data['per_serving']['carbs']);

        // Fat: chicken (2.6*2=5.2g) + rice (round(0.7*1.5=1.05, 1)=1.0g) + oil (100*0.1=10g) = 16.2g total -> 8.1g per serving
        $this->assertEquals(16.2, $data['whole_recipe']['fat']);
        $this->assertEquals(8.1, $data['per_serving']['fat']);

        // Macronutrient percentages are populated and total ~100%
        $macro = $data['macro_distribution'];
        $this->assertGreaterThan(20, $macro['protein_pct']);
        $this->assertGreaterThan(45, $macro['carbs_pct']);
        $this->assertGreaterThan(10, $macro['fat_pct']);
        $this->assertEquals(100.0, round($macro['protein_pct'] + $macro['carbs_pct'] + $macro['fat_pct'], 0));

        // Sources are cited
        $this->assertNotEmpty($data['sources']);
    }

    public function test_raw_vs_cooked_nutrition_distinction(): void
    {
        $rawRice = Food::where('source_food_id', 'FDC-169756')->firstOrFail();
        $cookedRice = Food::where('source_food_id', 'FDC-169757')->firstOrFail();

        $calc = app(NutritionCalculatorService::class);

        $resRaw = $calc->calculate([['food_id' => $rawRice->id, 'quantity' => 100, 'unit' => 'g']], 1);
        $resCooked = $calc->calculate([['food_id' => $cookedRice->id, 'quantity' => 100, 'unit' => 'g']], 1);

        // 100g raw rice is ~365 kcal, while 100g cooked rice is ~130 kcal due to water absorption
        $this->assertEquals(365.0, $resRaw['whole_recipe']['calories']);
        $this->assertEquals(130.0, $resCooked['whole_recipe']['calories']);
    }

    public function test_unit_conversions_for_piece_and_volume(): void
    {
        $egg = Food::where('source_food_id', 'FDC-171287')->firstOrFail(); // piece_weight_g = 50g
        $oil = Food::where('source_food_id', 'CIQUAL-17440')->firstOrFail(); // tbsp_weight_g = 13.5g

        $payload = [
            'servings' => 1,
            'ingredients' => [
                ['food_id' => $egg->id, 'quantity' => 2, 'unit' => 'piece'],
                ['food_id' => $oil->id, 'quantity' => 1, 'unit' => 'tbsp'],
            ],
        ];

        $response = $this->postJson(route('tools.nutrition-calculator.calculate'), $payload);
        $response->assertOk();
        $data = $response->json();

        // 2 eggs = 100g -> 143 kcal. 1 tbsp oil = 13.5g -> 119.3 kcal. Total ~262.3 kcal.
        $this->assertEquals(100.0, $data['ingredients'][0]['grams']);
        $this->assertEquals(13.5, $data['ingredients'][1]['grams']);
        $this->assertEquals(262.3, $data['whole_recipe']['calories']);
    }

    public function test_refusal_to_convert_volume_when_density_is_unknown(): void
    {
        $source = NutritionSource::first();
        $foodWithoutDensity = Food::create([
            'nutrition_source_id' => $source->id,
            'source_food_id' => 'TEST-NO-DENSITY',
            'name' => 'Mystery Powder',
            'name_en' => 'Mystery Powder',
            'name_fr' => 'Poudre mystère',
            'category' => 'Baking & Sweets',
            'preparation_state' => 'raw',
            'density_g_per_ml' => null, // No density!
            'is_active' => true,
        ]);
        $foodWithoutDensity->nutrients()->create([
            'calories_kcal' => 200,
            'protein_g' => 5,
            'carbohydrates_g' => 40,
            'fat_g' => 2,
        ]);

        $payload = [
            'servings' => 1,
            'ingredients' => [
                ['food_id' => $foodWithoutDensity->id, 'quantity' => 100, 'unit' => 'ml'],
            ],
        ];

        $response = $this->postJson(route('tools.nutrition-calculator.calculate'), $payload);
        $response->assertOk();
        $data = $response->json();

        // Does not invent 0 or arbitrary weight, but records a clear warning
        $this->assertNotEmpty($data['warnings']);
        $this->assertStringContainsString('Cannot convert unit', $data['warnings'][0]['message']);
        $this->assertEquals(0, $data['whole_recipe']['calories']);
    }

    public function test_validation_rejects_invalid_inputs(): void
    {
        // Zero servings
        $this->postJson(route('tools.nutrition-calculator.calculate'), [
            'servings' => 0,
            'ingredients' => [['food_id' => 1, 'quantity' => 100, 'unit' => 'g']],
        ])->assertStatus(422);

        // Empty ingredients
        $this->postJson(route('tools.nutrition-calculator.calculate'), [
            'servings' => 2,
            'ingredients' => [],
        ])->assertStatus(422);
    }

    public function test_seo_sitemap_and_llms_include_nutrition_calculator(): void
    {
        $sitemap = $this->get('/sitemap.xml')->assertOk()->content();
        $this->assertStringContainsString(route('tools.nutrition-calculator'), $sitemap);

        $llms = $this->get('/llms.txt')->assertOk()->content();
        $this->assertStringContainsString(route('tools.nutrition-calculator'), $llms);
    }

    public function test_recipe_page_renders_nutrition_card(): void
    {
        $recipe = Recipe::create([
            'tasty_id' => 999999,
            'name' => 'High-Protein Lemon Chicken',
            'description' => 'A delicious high protein dinner.',
            'num_servings' => 4,
            'nutrition' => [
                'calories' => 380,
                'protein' => 42,
                'carbohydrates' => 12,
                'fat' => 14,
                'fiber' => 2,
                'sugar' => 1,
            ],
            'sections' => [
                [
                    'name' => 'Main',
                    'components' => [
                        ['raw_text' => '4 chicken breasts'],
                        ['raw_text' => '2 tbsp olive oil'],
                    ],
                ],
            ],
            'instructions' => [
                ['display_text' => 'Sear chicken until golden.'],
            ],
        ]);

        $response = $this->get(route('recipes.show', $recipe));
        $response->assertOk();
        $response->assertSee('Nutrition Facts', false);
        $response->assertSee('380', false); // Calories
        $response->assertSee('42', false);  // Protein
        $response->assertSee(route('tools.nutrition-calculator'), false);
    }
}
