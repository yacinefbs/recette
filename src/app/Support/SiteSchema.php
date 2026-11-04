<?php

namespace App\Support;

class SiteSchema
{
    public static function json(): string
    {
        $root = url('/');
        $address = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => config('app.contact_address.street'),
            'addressLocality' => config('app.contact_address.city'),
            'addressRegion' => config('app.contact_address.region'),
            'postalCode' => config('app.contact_address.postal_code'),
            'addressCountry' => config('app.contact_address.country'),
        ]);
        $hasAddress = count($address) > 1;

        return json_encode([
            '@context' => 'https://schema.org',
            '@graph' => [
                array_filter([
                    '@type' => 'Organization',
                    '@id' => $root.'#organization',
                    'name' => config('app.name'),
                    'url' => $root,
                    'logo' => asset('assets/images/logo.svg'),
                    'email' => config('app.contact_email'),
                    'telephone' => config('app.contact_phone'),
                    'address' => $hasAddress ? $address : null,
                    'sameAs' => array_values(array_filter([config('services.facebook.page_url')])),
                ]),
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
