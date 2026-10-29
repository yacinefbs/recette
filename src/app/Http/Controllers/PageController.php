<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    public const PAGES = [
        'about' => 'About us',
        'contact' => 'Contact',
        'privacy-policy' => 'Privacy Policy',
        'terms' => 'Terms of Use',
    ];

    public function show(string $slug)
    {
        abort_unless(isset(self::PAGES[$slug]), 404);

        $site = config('app.name');
        $title = self::PAGES[$slug];

        $descriptions = [
            'about' => "Learn more about $site, a cooking recipe website with ingredients, detailed steps and preparation times.",
            'contact' => "Contact the $site team for questions, suggestions or content requests.",
            'privacy-policy' => "How $site collects, uses and protects your data, including cookies, Google Analytics and Google AdSense.",
            'terms' => "Terms of use of $site.",
        ];

        $seo = [
            'title' => "$title | $site",
            'description' => $descriptions[$slug],
            'canonical' => route('pages.show', $slug),
        ];

        return view("pages.$slug", [
            'seo' => $seo,
            'pageTitle' => $title,
            'site' => $site,
            'contactEmail' => config('app.contact_email'),
        ]);
    }
}
