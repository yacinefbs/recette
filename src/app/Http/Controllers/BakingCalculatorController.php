<?php

namespace App\Http\Controllers;

class BakingCalculatorController extends Controller
{
    public function index()
    {
        $canonical = route('tools.baking-calculator');
        $faqs = [
            [
                'question' => 'What is a baking calculator?',
                'answer' => 'A baking calculator scales flour, water, salt, yeast, and other dough ingredients by weight. This one supports baker’s percentages, target dough weight, portion counts, flour blends, and sourdough starter.',
            ],
            [
                'question' => 'What is baker’s percentage?',
                'answer' => 'Baker’s percentage expresses each ingredient as a percentage of the total flour weight. Flour is 100%, so 650 g water with 1,000 g flour is 65% hydration.',
            ],
            [
                'question' => 'How do you calculate dough hydration?',
                'answer' => 'Divide the total water weight by the total flour weight and multiply by 100. When starter is included, count the flour and water inside the starter in those totals.',
            ],
            [
                'question' => 'What is the ideal hydration for bread?',
                'answer' => 'There is no single ideal hydration. Flour type, recipe, technique, and the dough you want all matter. The calculator’s preset percentages are editable starting points, not rules.',
            ],
            [
                'question' => 'How much water do I need for 1 kg of flour?',
                'answer' => 'At 65% hydration, 1 kg total flour uses 650 g total water. If the dough includes sourdough starter, subtract the water already present in the starter from the water added separately.',
            ],
            [
                'question' => 'How much yeast should I use for bread?',
                'answer' => 'It depends on the recipe, yeast type, fermentation time, and temperature. Start with a recipe-specific percentage; the calculator lets you adjust it and labels yeast conversions as approximate.',
            ],
            [
                'question' => 'How do I calculate pizza dough for 4 pizzas?',
                'answer' => 'Choose the portion mode, enter 4 portions, and set the desired dough-ball weight. The calculator finds the total dough weight and scales the ingredients to match.',
            ],
            [
                'question' => 'Can I use this calculator for sourdough bread?',
                'answer' => 'Yes. Enable sourdough starter and enter its hydration and its flour as a percentage of total flour. The starter’s flour and water are included in the dough totals and are not counted twice.',
            ],
            [
                'question' => 'What is the difference between fresh yeast and dry yeast?',
                'answer' => 'They contain different amounts of water and are not interchangeable gram for gram. The calculator uses an editable approximate ratio; check the yeast maker’s directions and your recipe.',
            ],
            [
                'question' => 'How much dough do I need per pizza?',
                'answer' => 'It depends on the pizza’s diameter, crust thickness, and style. Enter the dough-ball weight from your recipe or preference; the calculator scales the batch without prescribing a universal size.',
            ],
        ];

        return view('tools.baking-calculator', [
            'pageTitle' => 'Baking Calculator',
            'wide' => true,
            'faqs' => $faqs,
            'seo' => [
                'title' => 'Baking Calculator – Bread & Pizza Dough Ingredient Calculator',
                'description' => 'Calculate flour, water, yeast and salt quantities with our free baking calculator. Adjust dough hydration, baker’s percentages and batch sizes for bread and pizza.',
                'canonical' => $canonical,
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Baking Calculator', 'item' => $canonical],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => 'Baking Calculator – '.config('app.name', 'TopicTrail'),
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'All',
                        'url' => $canonical,
                        'description' => 'Calculate bread and pizza dough ingredient weights, hydration and baker’s percentages with flour blends and sourdough starter support.',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => array_map(fn (array $faq) => [
                            '@type' => 'Question',
                            'name' => $faq['question'],
                            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['answer']],
                        ], $faqs),
                    ],
                ],
            ],
        ]);
    }
}
