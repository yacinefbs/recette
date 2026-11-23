<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FuelCostCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_calculator_has_seo_metadata_open_graph_and_working_controls(): void
    {
        $this->get(route('tools.fuel.calculator'))
            ->assertOk()
            ->assertSee('<title>Fuel Cost Calculator – Estimate Your Trip Fuel Expenses</title>', false)
            ->assertSee('<link rel="canonical" href="'.route('tools.fuel.calculator').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('assets/images/social/fuel-cost-calculator-og.jpg').'">', false)
            ->assertSee('<meta property="og:image:width" content="1200">', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('fuel-calculator-core.js', false)
            ->assertSee('fuel-calculator.js', false)
            ->assertSee('Quick Fuel Calculator')
            ->assertSee('Advanced Trip Cost Calculator')
            ->assertSee('Fuel Budget Planner')
            ->assertSee('Vehicle Comparison Calculator')
            ->assertSee('Saved Trips');
    }

    public function test_fuel_guides_have_distinct_canonicals_and_are_indexed(): void
    {
        $roadTrip = route('tools.fuel.guide', 'road-trip-cost-calculator');
        $consumption = route('tools.fuel.guide', 'fuel-consumption-calculator');

        $this->get($roadTrip)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$roadTrip.'">', false)
            ->assertSee('<title>Road Trip Cost Calculator – Estimate Your Travel Budget</title>', false);

        $this->get($consumption)
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$consumption.'">', false)
            ->assertSee('<title>Fuel Consumption Calculator – Convert MPG, km/L &amp; L/100 km</title>', false);

        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('<loc>'.route('tools.fuel.calculator').'</loc>', false)
            ->assertSee('<loc>'.$roadTrip.'</loc>', false)
            ->assertSee('<loc>'.$consumption.'</loc>', false);

        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee(route('tools.fuel.calculator'))
            ->assertSee($roadTrip)
            ->assertSee($consumption);
    }

    public function test_calculator_is_discoverable_from_navigation_and_tools_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee(route('tools.fuel.calculator'));

        $this->get(route('tools.converter'))
            ->assertOk()
            ->assertSee(route('tools.fuel.calculator'));
    }
}
