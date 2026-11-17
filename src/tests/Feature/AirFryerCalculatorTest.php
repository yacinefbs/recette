<?php

namespace Tests\Feature;

use App\Services\AirFryerCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirFryerCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculator_page_has_seo_og_data_and_sourced_food_catalog(): void
    {
        $response = $this->get(route('tools.air-fryer-calculator'));

        $response->assertOk();
        $response->assertSee('<title>Air Fryer Calculator – Cooking Times &amp; Temperatures</title>', false);
        $response->assertSee('<link rel="canonical" href="'.route('tools.air-fryer-calculator').'">', false);
        $response->assertSee('<meta property="og:image" content="'.asset('assets/images/social/air-fryer-calculator-og.jpg').'">', false);
        $response->assertSee('"@type":"WebApplication"', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('Mississippi State University Extension, Publication 3577');
        $response->assertSee('Start Cooking Timer');

        $this->assertCount(49, app(AirFryerCalculatorService::class)->foods());
    }

    public function test_calculator_returns_published_setting_and_food_safety_guidance(): void
    {
        $response = $this->postJson(route('tools.air-fryer-calculator.calculate'), [
            'food' => 'chicken-breast',
            'condition' => 'fresh',
            'quantity' => 300,
            'preheat' => 'yes',
            'unit' => 'c',
        ]);

        $response->assertOk()
            ->assertJsonPath('temperature', 193)
            ->assertJsonPath('temperature_f', 380)
            ->assertJsonPath('temperature_c', 193)
            ->assertJsonPath('time_min', 12)
            ->assertJsonPath('time_max', 12)
            ->assertJsonPath('quantity_grams', 300)
            ->assertJsonPath('safety.temperature', 74)
            ->assertJsonPath('source.name', 'Mississippi State University Extension, Publication 3577');

        $this->assertStringContainsString('does not change', $response->json('warnings.0'));
    }

    public function test_temperature_conversion_and_documented_size_variations_are_deterministic(): void
    {
        $calculator = app(AirFryerCalculatorService::class);
        $thin = $calculator->calculate('french-fries', 'frozen', 'thin', 300, 'unsure', 'f');
        $thick = $calculator->calculate('french-fries', 'frozen', 'thick', 900, 'no', 'c');

        $this->assertSame(400, $thin['temperature']);
        $this->assertSame(14, $thin['time_min']);
        $this->assertSame(18, $thick['time_min']);
        $this->assertSame(204, $thick['temperature']);
        $this->assertStringContainsString('does not provide a separate no-preheat adjustment', $thick['warnings'][1]);
    }

    public function test_unsupported_food_conditions_and_sizes_are_not_given_estimated_values(): void
    {
        $this->postJson(route('tools.air-fryer-calculator.calculate'), [
            'food' => 'asparagus',
            'condition' => 'frozen',
            'quantity' => 300,
            'preheat' => 'unsure',
            'unit' => 'c',
        ])->assertUnprocessable();

        $this->postJson(route('tools.air-fryer-calculator.calculate'), [
            'food' => 'french-fries',
            'condition' => 'frozen',
            'size' => 'regular',
            'quantity' => 300,
            'preheat' => 'unsure',
            'unit' => 'c',
        ])->assertUnprocessable();
    }

    public function test_invalid_quantity_and_food_are_rejected(): void
    {
        $this->postJson(route('tools.air-fryer-calculator.calculate'), [
            'food' => 'unknown-food',
            'condition' => 'fresh',
            'quantity' => 0,
            'preheat' => 'unsure',
            'unit' => 'c',
        ])->assertUnprocessable()->assertJsonValidationErrors(['food', 'quantity']);
    }

    public function test_new_tool_is_listed_in_sitemap_and_llms_txt(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee(route('tools.air-fryer-calculator'));

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee(route('tools.air-fryer-calculator'));
    }
}
