<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Models\NutritionSource;
use App\Services\Nutrition\FoodSearchService;
use App\Services\Nutrition\NutritionCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NutritionCalculatorController extends Controller
{
    public function __construct(
        private readonly FoodSearchService $searchService,
        private readonly NutritionCalculatorService $calculatorService
    ) {}

    public function index()
    {
        $site = config('app.name', 'TopicTrail');
        $canonical = route('tools.nutrition-calculator');

        $seo = [
            'title' => 'Free Nutrition Calculator – Calories, Protein, Carbs & Fat | '.$site,
            'description' => 'Estimate calories, protein, carbohydrates, and fat per serving or for your whole recipe. Powered by verified USDA and ANSES-Ciqual databases.',
            'canonical' => $canonical,
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => 'Nutrition Calculator', 'item' => $canonical],
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebApplication',
                    'name' => 'Free Nutrition Calculator – '.$site,
                    'applicationCategory' => 'HealthApplication',
                    'operatingSystem' => 'All',
                    'url' => $canonical,
                    'description' => 'Calculate verified macronutrients and calories per serving for any recipe from scientific food databases (USDA & Ciqual).',
                    'offers' => [
                        '@type' => 'Offer',
                        'price' => '0',
                        'priceCurrency' => 'USD',
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'FAQPage',
                    'mainEntity' => [
                        [
                            '@type' => 'Question',
                            'name' => 'How are calories per serving calculated?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'The total calories of the recipe are calculated by multiplying each ingredient weight by its verified reference value per 100g, summing them together, and then dividing the total by the number of servings.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'Why does food state (raw vs cooked) matter for nutrition calculations?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'During cooking, foods absorb or lose water and fat. For example, 100g of raw rice has about 365 kcal, but once cooked with water, 100g of cooked rice has only about 130 kcal because water adds weight without calories. Our calculator keeps raw and cooked ingredients separate to ensure accurate estimations.',
                            ],
                        ],
                        [
                            '@type' => 'Question',
                            'name' => 'What nutritional databases are used?',
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text' => 'All nutrition values are grounded on official scientific databases: USDA FoodData Central (Foundation & FNDDS) and ANSES-Ciqual 2025. We do not invent values or use unverified crowdsourced estimates.',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Sample initial common foods for fast selection
        $initialFoods = Food::query()
            ->with(['nutrients', 'source'])
            ->active()
            ->whereIn('source_food_id', [
                'FDC-171077', // chicken breast raw
                'FDC-169756', // white rice raw
                'CIQUAL-17440', // olive oil
                'FDC-171287', // egg
                'FDC-168933', // pasta raw
                'FDC-175167', // salmon raw
                'FDC-170417-AVO', // avocado
                'FDC-169705', // oats
            ])
            ->get()
            ->map(fn (Food $f) => $this->searchService->format($f));

        $sources = NutritionSource::all();

        return view('tools.nutrition-calculator.index', [
            'seo' => $seo,
            'pageTitle' => 'Free Nutrition Calculator',
            'initialFoods' => $initialFoods,
            'sources' => $sources,
            'wide' => true,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) > 100) {
            $q = mb_substr($q, 0, 100);
        }

        $results = $this->searchService->search($q, 25);

        return response()->json([
            'results' => $results,
        ]);
    }

    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'servings' => ['required', 'integer', 'min:1', 'max:100'],
            'ingredients' => ['required', 'array', 'min:1', 'max:100'],
            'ingredients.*.food_id' => ['required', 'integer', 'exists:foods,id'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:100000'],
            'ingredients.*.unit' => ['required', 'string', 'max:30'],
        ]);

        $result = $this->calculatorService->calculate(
            $validated['ingredients'],
            (int) $validated['servings']
        );

        return response()->json($result);
    }
}
