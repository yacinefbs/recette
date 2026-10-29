<?php

namespace App\Http\Controllers;

use App\Models\Recipe;

class SeoController extends Controller
{
    public function robots()
    {
        $body = "User-agent: *\nAllow: /\nDisallow: /*?q=\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function ads()
    {
        return response("google.com, pub-3690879797577737, DIRECT, f08c47fec0942fa0\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap()
    {
        $latest = Recipe::max('updated_at');
        $entries = [
            [url('/'), $latest, 'daily', '1.0'],
            [route('recipes.index'), $latest, 'daily', '0.9'],
        ];

        foreach (array_keys(PageController::PAGES) as $slug) {
            $entries[] = [route('pages.show', $slug), null, 'yearly', '0.3'];
        }

        foreach (Recipe::select('id', 'updated_at')->orderBy('id')->cursor() as $recipe) {
            $entries[] = [route('recipes.show', $recipe), $recipe->updated_at, 'monthly', '0.8'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($entries as [$loc, $lastmod, $freq, $prio]) {
            $xml .= '  <url><loc>'.e($loc).'</loc>';
            if ($lastmod) {
                $xml .= '<lastmod>'.\Illuminate\Support\Carbon::parse($lastmod)->toAtomString().'</lastmod>';
            }
            $xml .= "<changefreq>$freq</changefreq><priority>$prio</priority></url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
