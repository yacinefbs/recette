<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class ToolController extends Controller
{
    public function cookingTimer()
    {
        $site = config('app.name', 'TopicTrail');
        $canonical = route('tools.cooking-timer');

        return view('tools.cooking-timer', [
            'pageTitle' => 'Free Online Cooking Timer',
            'seo' => [
                'title' => 'Free Cooking Timer – Multiple Kitchen Timers Online',
                'description' => 'Use our free online cooking timer to manage multiple timers for eggs, pasta, rice, baking and everyday cooking.',
                'canonical' => $canonical,
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Tools', 'item' => route('tools.converter')],
                            ['@type' => 'ListItem', 'position' => 3, 'name' => 'Cooking Timer', 'item' => $canonical],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => 'Free Cooking Timer – '.$site,
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'All',
                        'url' => $canonical,
                        'description' => 'Manage multiple cooking timers for everyday kitchen tasks.',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                ],
            ],
        ]);
    }

    public function converter()
    {
        $site = config('app.name');

        return view('tools.converter', [
            'site' => $site,
            'pageTitle' => 'Cooking Converter',
            'seo' => [
                'title' => "Cooking Converter: Cups to Grams, °F to °C | $site",
                'description' => 'Free cooking converter: cups to grams, ounces to grams, tablespoons to ml and oven temperatures °F to °C, with handy ingredient charts.',
                'canonical' => route('tools.converter'),
            ],
        ]);
    }

    public function whatCanICook(Request $request)
    {
        $site = config('app.name');
        $raw = trim((string) $request->query('ingredients', ''));
        $terms = collect(preg_split('/[,\n]+/', mb_strtolower($raw)))
            ->map(fn ($t) => trim($t))->filter(fn ($t) => mb_strlen($t) >= 2)->unique()->take(10)->values();

        $results = collect();
        if ($terms->isNotEmpty()) {
            $results = Recipe::query()->select('id', 'name', 'image_path', 'sections', 'total_time_minutes')->cursor()
                ->map(function ($recipe) use ($terms) {
                    $text = mb_strtolower(collect($recipe->sections ?? [])
                        ->flatMap(fn ($s) => collect($s['components'] ?? [])->pluck('raw_text'))->implode(' | '));
                    $matched = $terms->filter(fn ($t) => str_contains($text, $t))->values();

                    return ['recipe' => $recipe, 'matched' => $matched];
                })
                ->filter(fn ($r) => $r['matched']->isNotEmpty())
                ->sortByDesc(fn ($r) => $r['matched']->count())
                ->take(24)->values();
        }

        return view('tools.what-can-i-cook', [
            'site' => $site,
            'pageTitle' => 'What Can I Cook?',
            'raw' => $raw,
            'terms' => $terms,
            'results' => $results,
            'seo' => [
                'title' => "What Can I Cook? Find Recipes by Ingredients | $site",
                'description' => 'Type the ingredients you have at home and find easy recipes you can make right now.',
                'canonical' => route('tools.what'),
                'robots' => $terms->isNotEmpty() ? 'noindex,follow' : null,
            ],
        ]);
    }
}
