<?php

namespace App\Support;

class SiteSchema
{
    public static function json(): string
    {
        $root = url('/');

        return json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $root.'#organization',
                    'name' => config('app.name'),
                    'url' => $root,
                    'logo' => asset('assets/images/logo.svg'),
                    'email' => config('app.contact_email'),
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $root.'#website',
                    'url' => $root,
                    'name' => config('app.name'),
                    'inLanguage' => 'en',
                    'publisher' => ['@id' => $root.'#organization'],
                    'potentialAction' => [
                        '@type' => 'SearchAction',
                        'target' => route('recipes.index').'?q={search_term_string}',
                        'query-input' => 'required name=search_term_string',
                    ],
                ],
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
    }
}
