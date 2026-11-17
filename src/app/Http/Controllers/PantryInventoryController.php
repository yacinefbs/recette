<?php

namespace App\Http\Controllers;

use App\Services\PantryRecipeMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PantryInventoryController extends Controller
{
    public function index(): View
    {
        $canonical = route('tools.pantry-inventory');

        return view('tools.pantry-inventory', [
            'pageTitle' => 'Free Pantry Inventory Manager',
            'seo' => [
                'title' => 'Free Pantry Inventory Manager – Track Your Food',
                'description' => 'Track your kitchen ingredients, quantities and expiration dates. Reduce food waste and discover recipes with what you have.',
                'canonical' => $canonical,
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Pantry Inventory Manager', 'item' => $canonical],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => 'Pantry Inventory Manager',
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'All',
                        'url' => $canonical,
                        'description' => 'A private, browser-based tool for tracking kitchen ingredients, stock and recorded date labels.',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                ],
            ],
        ]);
    }

    public function recipes(Request $request, PantryRecipeMatcher $matcher): JsonResponse
    {
        $data = $request->validate([
            'ingredients' => ['required', 'array', 'min:1', 'max:30'],
            'ingredients.*.name' => ['required', 'string', 'min:1', 'max:80'],
            'ingredients.*.quantity' => ['required', 'numeric', 'min:0'],
            'ingredients.*.unit' => ['required', Rule::in(['g', 'kg', 'ml', 'L', 'pieces', 'packs', 'cans', 'bottles', 'bags'])],
            'ingredients.*.expiry_date' => ['nullable', 'date_format:Y-m-d'],
            'prioritize_expiring' => ['sometimes', 'boolean'],
        ]);

        return response()->json([
            'recipes' => $matcher->match($data['ingredients'], (bool) ($data['prioritize_expiring'] ?? false)),
        ]);
    }
}
