<?php

namespace App\Http\Controllers;

class FuelCostCalculatorController extends Controller
{
    private const GUIDES = [
        'road-trip-cost-calculator' => [
            'title' => 'Road Trip Cost Calculator – Estimate Your Travel Budget',
            'description' => 'Plan a road trip budget with estimated fuel, tolls, parking and shared passenger costs. Use your own vehicle details and rates.',
            'heading' => 'Road Trip Cost Calculator',
            'intro' => 'Build a practical road trip budget by adding fuel, tolls, parking and other trip expenses, then see the total and an estimated cost per passenger.',
            'mode' => 'advanced',
            'section' => 'How to Estimate a Road Trip Budget',
            'content' => 'Start with the distance you plan to drive and the vehicle’s real-world fuel consumption. For a return journey, enter the one-way distance and select round trip. Add known tolls, parking, accommodation or other costs in the same currency. Divide shared costs among passengers, or choose to keep additional expenses individual. The estimate helps compare scenarios, but does not include unexpected detours, traffic, price changes or every possible travel expense.',
        ],
        'fuel-consumption-calculator' => [
            'title' => 'Fuel Consumption Calculator – Convert MPG, km/L & L/100 km',
            'description' => 'Calculate trip fuel use and convert L/100 km, km/L, US MPG and UK MPG with distinct gallon standards.',
            'heading' => 'Fuel Consumption Calculator',
            'intro' => 'Estimate the fuel needed for a journey and understand how consumption units compare. US MPG and UK MPG use different gallon sizes, so this calculator keeps their conversion factors separate.',
            'mode' => 'quick',
            'section' => 'How to Compare Fuel Consumption Units',
            'content' => 'Liters per 100 kilometers measures the fuel needed to travel a fixed distance, so a lower number generally indicates lower consumption. Kilometers per liter and miles per gallon instead describe distance traveled per unit of fuel, so higher numbers generally indicate greater efficiency. To compare vehicles accurately, use the same test conditions and distinguish US gallons from UK imperial gallons. Official ratings and real-world results may differ because of speed, weather, load, traffic and driving style.',
        ],
    ];

    public function index()
    {
        return $this->render(null);
    }

    public function guide(string $slug)
    {
        abort_unless(isset(self::GUIDES[$slug]), 404);

        return $this->render(self::GUIDES[$slug] + ['slug' => $slug]);
    }

    private function render(?array $guide)
    {
        $canonical = $guide
            ? route('tools.fuel.guide', $guide['slug'])
            : route('tools.fuel.calculator');
        $title = $guide['title'] ?? 'Fuel Cost Calculator – Estimate Your Trip Fuel Expenses';
        $description = $guide['description'] ?? 'Calculate your trip fuel cost, fuel consumption and travel expenses. Estimate one-way and round-trip costs with our free fuel cost calculator.';
        $heading = $guide['heading'] ?? 'Fuel Cost Calculator';

        return view('tools.fuel.calculator', [
            'pageTitle' => $heading,
            'wide' => true,
            'guide' => $guide,
            'seo' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Fuel Cost Calculator', 'item' => route('tools.fuel.calculator')],
                            ...($guide ? [['@type' => 'ListItem', 'position' => 4, 'name' => $heading, 'item' => $canonical]] : []),
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => $heading.' – '.config('app.name', 'TopicTrail'),
                        'applicationCategory' => 'TravelApplication',
                        'operatingSystem' => 'All',
                        'url' => $canonical,
                        'description' => $description,
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => [
                            [
                                '@type' => 'Question',
                                'name' => 'How much fuel do I need for a trip?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Multiply the total distance in kilometers by consumption in L/100 km and divide by 100. For example, 700 km at 6.5 L/100 km needs about 45.5 liters.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'What is the difference between US MPG and UK MPG?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'US and UK MPG use different gallon sizes. A US gallon is about 3.785 liters and a UK imperial gallon is about 4.546 liters, so the same MPG value does not represent the same fuel economy.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'Does the calculator find routes or current fuel prices?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. Enter the distance from your own route plan and the current fuel or electricity price you want to use. Optional start and destination fields are labels only.'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
