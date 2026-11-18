<?php

namespace App\Http\Controllers;

class RecipeCostCalculatorController extends Controller
{
    public function index()
    {
        $canonical = route('tools.recipe-cost-calculator');
        $site = config('app.name', 'TopicTrail');

        return view('tools.recipe-cost-calculator', [
            'pageTitle' => 'Recipe Cost Calculator',
            'seo' => [
                'title' => 'Free Recipe Cost Calculator – Cost Per Serving',
                'description' => 'Calculate recipe costs, ingredient prices and cost per serving. Compare recipes and plan your food budget with this free calculator.',
                'canonical' => $canonical,
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Recipe Cost Calculator', 'item' => $canonical],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => 'Recipe Cost Calculator – '.$site,
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'All',
                        'url' => $canonical,
                        'description' => 'Calculate the ingredient cost and cost per serving for your recipes.',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => [
                            [
                                '@type' => 'Question',
                                'name' => 'How do I calculate the cost of a recipe?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'For each ingredient, divide the quantity used by the package quantity and multiply by the package price. Add the ingredient costs to find the total.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'How do I calculate cost per serving?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Divide the total ingredient cost by the number of servings.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'Can I use different currencies?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'Choose one currency for each recipe. The calculator does not convert currencies or compare recipes entered in different currencies.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'Does this calculator include labor and packaging?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. The current version calculates ingredient costs only. Labor, packaging, taxes, and other expenses are not included.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'Are ingredient prices automatically updated?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. Enter your own package prices. TopicTrail does not fetch or assume local grocery prices.'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
