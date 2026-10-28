<?php

namespace App\Http\Controllers;

use App\Models\Recipe;

class HomeController extends Controller
{
    public function index()
    {
        $site = config('app.name');

        $seo = [
            'title' => "$site – Easy and delicious cooking recipes",
            'description' => 'Discover easy and delicious cooking recipes: ingredients, detailed steps, preparation times and photos to cook every day.',
            'canonical' => url('/'),
            'type' => 'website',
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebSite',
                    'name' => $site,
                    'url' => url('/'),
                    'inLanguage' => 'en',
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
