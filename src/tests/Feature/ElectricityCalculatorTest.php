<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ElectricityCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_electricity_calculator_renders_seo_and_functional_tools(): void
    {
        $response = $this->get(route('tools.electricity.calculator'))
            ->assertOk()
            ->assertSee('<title>Electricity Cost Calculator – Calculate Your Energy Usage</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('tools.electricity.calculator').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('assets/images/social/electricity-cost-calculator-og.jpg').'">', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('electricity-calculator-core.js', false)
            ->assertSee('electricity-calculator.js', false)
            ->assertSee('Calculate Electricity Cost')
            ->assertSee('Multiple appliances')
            ->assertSee('Energy Savings Calculator')
            ->assertSee('Standby Power Calculator')
            ->assertSee('Explore More Free Tools')
            ->assertDontSee('"@type":"FAQPage"', false);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $this->assertFileExists(public_path('assets/images/social/electricity-cost-calculator-og.jpg'));
    }

    public function test_initial_appliance_guides_have_unique_metadata_and_prefilled_power(): void
    {
        $devices = [
            'air-conditioner' => ['Air Conditioner Electricity Cost Calculator', '1500'],
            'refrigerator' => ['Refrigerator Electricity Cost Calculator', '150'],
            'electric-water-heater' => ['Electric Water Heater Cost Calculator', '3000'],
        ];

        foreach ($devices as $slug => [$title, $power]) {
            $this->get(route('tools.electricity.appliance', $slug))
                ->assertOk()
                ->assertSee('<title>'.$title.'</title>', false)
                ->assertSee('<h1>'.$title.'</h1>', false)
                ->assertSee('data-appliance-power="'.$power.'"', false)
                ->assertSee('data-appliance-name="', false)
                ->assertSee('"@type":"WebApplication"', false);
        }
    }

    public function test_calculator_pages_are_discoverable_from_sitemap_llms_and_navigation(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.route('tools.electricity.calculator').'</loc>', false)
            ->assertSee('<loc>'.route('tools.electricity.appliance', 'air-conditioner').'</loc>', false)
            ->assertSee('<loc>'.route('tools.electricity.appliance', 'refrigerator').'</loc>', false)
            ->assertSee('<loc>'.route('tools.electricity.appliance', 'electric-water-heater').'</loc>', false);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee(route('tools.electricity.calculator'));

        $this->get('/')
            ->assertOk()
            ->assertSee(route('tools.electricity.calculator'));

        $this->get(route('tools.converter'))
            ->assertOk()
            ->assertSee(route('tools.electricity.calculator'));

        $this->get(route('pages.show', 'terms'))
            ->assertOk()
            ->assertSee('Calculators and estimates')
            ->assertSee('We do not guarantee a particular cost or saving.');
    }
}
