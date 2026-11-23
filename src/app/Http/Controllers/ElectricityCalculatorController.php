<?php

namespace App\Http\Controllers;

class ElectricityCalculatorController extends Controller
{
    private const APPLIANCES = [
        'air-conditioner' => [
            'name' => 'Air Conditioner',
            'power' => 1500,
            'heading' => 'Air Conditioner Electricity Cost Calculator',
            'title' => 'Air Conditioner Electricity Cost Calculator',
            'description' => 'Estimate how much electricity an air conditioner uses and costs. Adjust watts, daily hours and your own electricity rate in this free calculator.',
            'intro' => 'Air conditioners can draw very different amounts of power depending on capacity, efficiency, weather and whether the compressor is running. Use the appliance label or manual when possible; the sample wattage below is only a starting point.',
            'example' => 'For example, a 1,500 W unit used for 6 hours a day uses 9 kWh per day before accounting for days of use. Multiply the energy by your own price per kWh to estimate its cost.',
        ],
        'refrigerator' => [
            'name' => 'Refrigerator',
            'power' => 150,
            'heading' => 'Refrigerator Electricity Cost Calculator',
            'title' => 'Refrigerator Electricity Cost Calculator',
            'description' => 'Estimate refrigerator energy use and cost with your own electricity rate. Enter its watts, average daily run time and days used in this free calculator.',
            'intro' => 'A refrigerator cycles on and off, so its label wattage is not the same as its average continuous draw. If available, use an energy label or plug-in energy meter to get a better estimate; the sample value is not a guaranteed daily average.',
            'example' => 'A 150 W refrigerator running for an estimated 8 equivalent hours per day uses 1.2 kWh per day. Actual consumption depends on the appliance, room temperature, settings, door use and age.',
        ],
        'electric-water-heater' => [
            'name' => 'Electric Water Heater',
            'power' => 3000,
            'heading' => 'Electric Water Heater Cost Calculator',
            'title' => 'Electric Water Heater Cost Calculator',
            'description' => 'Calculate estimated electricity consumption and cost for an electric water heater using its power, usage time and your own tariff.',
            'intro' => 'An electric water heater draws power while heating, but its daily energy use depends on water demand, tank insulation, thermostat settings and standby losses. Use the rated input from the unit label and estimate the time its element is actively heating.',
            'example' => 'A 3,000 W element heating for 1 hour uses 3 kWh. This simple estimate does not include cycling, heat loss or household hot-water patterns.',
        ],
    ];

    public function index()
    {
        return $this->render(null);
    }

    public function appliance(string $slug)
    {
        abort_unless(isset(self::APPLIANCES[$slug]), 404);

        return $this->render(self::APPLIANCES[$slug] + ['slug' => $slug]);
    }

    private function render(?array $device)
    {
        $canonical = $device
            ? route('tools.electricity.appliance', $device['slug'])
            : route('tools.electricity.calculator');
        $title = $device['title'] ?? 'Electricity Cost Calculator – Calculate Your Energy Usage';
        $description = $device['description'] ?? 'Calculate your electricity consumption and energy costs. Estimate daily, monthly and yearly expenses for your home appliances with our free electricity cost calculator.';
        $breadcrumbName = $device['name'] ?? 'Electricity Cost Calculator';

        return view('tools.electricity.calculator', [
            'pageTitle' => $breadcrumbName,
            'wide' => true,
            'device' => $device,
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'image' => asset('assets/images/social/electricity-cost-calculator-og.jpg'),
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Electricity Cost Calculator', 'item' => route('tools.electricity.calculator')],
                            ...($device ? [['@type' => 'ListItem', 'position' => 4, 'name' => $device['name'], 'item' => $canonical]] : []),
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => $title.' – '.config('app.name', 'TopicTrail'),
                        'applicationCategory' => 'UtilitiesApplication',
                        'operatingSystem' => 'All',
                        'url' => $canonical,
                        'description' => $description,
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                ],
            ],
        ]);
    }
}
