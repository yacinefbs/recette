<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $site = config('app.name');
        $links = '<'.url('/llms.txt').'>; rel="alternate"; type="text/markdown", <'.url('/sitemap.xml').'>; rel="sitemap"';

        if ($this->wantsMarkdown($request)) {
            $markdown = app(SeoController::class)->llms()->getContent();

            return response($markdown, 200, [
                'Content-Type' => 'text/markdown; charset=UTF-8',
                'Vary' => 'Accept',
                'Link' => $links,
            ]);
        }

        $recipes = Recipe::orderByDesc('id')->limit(8)->get();

        $seo = [
            'title' => "$site – Easy and delicious cooking recipes",
            'description' => 'Discover easy and delicious cooking recipes: ingredients, detailed steps, preparation times and photos to cook every day.',
            'canonical' => url('/'),
            'type' => 'website',
            // Organization and WebSite are emitted globally by SiteSchema
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    '@id' => url('/').'#webpage',
                    'url' => url('/'),
                    'name' => "$site – Easy and delicious cooking recipes",
                    'isPartOf' => ['@id' => url('/').'#website'],
                    'inLanguage' => 'en',
                    'mainEntity' => [
                        '@type' => 'ItemList',
                        'itemListElement' => $recipes->values()->map(fn ($r, $i) => [
                            '@type' => 'ListItem',
                            'position' => $i + 1,
                            'url' => route('recipes.show', $r),
                            'name' => $r->name,
                        ])->all(),
                    ],
                ],
            ],
        ];

        return response()
            ->view('home', ['recipes' => $recipes, 'seo' => $seo])
            ->header('Vary', 'Accept')
            ->header('Link', $links);
    }

    private function wantsMarkdown(Request $request): bool
    {
        $accept = strtolower((string) $request->header('Accept', ''));
        $md = strpos($accept, 'text/markdown');
        $html = strpos($accept, 'text/html');

        return $md !== false && ($html === false || $md < $html);
    }
}
