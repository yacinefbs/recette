<?php

namespace App\Http\Controllers;

use App\Models\Recipe;

class HomeController extends Controller
{
    public function index()
    {
        $site = config('app.name');

        $seo = [
            'title' => "$site – Recettes de cuisine faciles et gourmandes",
            'description' => 'Découvrez des recettes de cuisine faciles et gourmandes : ingrédients, étapes détaillées, temps de préparation et photos pour cuisiner chaque jour.',
            'canonical' => url('/'),
            'type' => 'website',
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $site,
                    'url' => url('/'),
                    'inLanguage' => 'fr',
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => url('/recipes').'?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'Organization',
                    'name' => $site,
                    'url' => url('/'),
                    'logo' => asset('assets/images/logo.png'),
                ],
            ],
        ];

        return view('home', [
            'recipes' => Recipe::orderByDesc('id')->limit(8)->get(),
            'seo' => $seo,
        ]);
    }
}
