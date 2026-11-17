<?php

namespace App\Http\Controllers;

use App\Services\AirFryerCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AirFryerCalculatorController extends Controller
{
    public function index(AirFryerCalculatorService $calculator)
    {
        $canonical = route('tools.air-fryer-calculator');
        $site = config('app.name', 'TopicTrail');

        return view('tools.air-fryer-calculator', [
            'pageTitle' => 'Air Fryer Calculator',
            'foods' => $calculator->foods(),
            'seo' => [
                'title' => 'Air Fryer Calculator – Cooking Times & Temperatures',
                'description' => 'Find recommended air fryer cooking times and temperatures for chicken, vegetables, fish, frozen foods and more.',
                'canonical' => $canonical,
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Air Fryer Calculator', 'item' => $canonical],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => 'Air Fryer Calculator – '.$site,
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'All',
                        'url' => $canonical,
                        'description' => 'Find sourced starting temperatures and cooking times for air fryer foods.',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'FAQPage',
                        'mainEntity' => [
                            [
                                '@type' => 'Question',
                                'name' => 'Does the calculator increase cooking time for larger quantities?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. The published chart does not validate quantity-based adjustments. The quantity is shown for reference only; avoid overcrowding and cook in batches if needed.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'Does a timer prove that meat is safely cooked?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. Use a food thermometer to check the recommended internal temperature. Cooking time alone cannot confirm food safety.'],
                            ],
                            [
                                '@type' => 'Question',
                                'name' => 'Are these exact air fryer cooking instructions?',
                                'acceptedAnswer' => ['@type' => 'Answer', 'text' => 'No. They are starting settings from a cited extension publication. Food size, amount, appliance, and basket loading can change results.'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function calculate(Request $request, AirFryerCalculatorService $calculator)
    {
        $foodSlugs = collect($calculator->foods())->pluck('slug')->all();
        $validated = $request->validate([
            'food' => ['required', 'string', Rule::in($foodSlugs)],
            'condition' => ['required', Rule::in(['fresh', 'frozen'])],
            'size' => ['nullable', Rule::in(['thin', 'thick'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:5000'],
            'preheat' => ['required', Rule::in(['yes', 'no', 'unsure'])],
            'unit' => ['required', Rule::in(['c', 'f'])],
        ]);

        try {
            return response()->json($calculator->calculate(
                $validated['food'],
                $validated['condition'],
                $validated['size'] ?? null,
                (int) $validated['quantity'],
                $validated['preheat'],
                $validated['unit'],
            ));
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }
}
