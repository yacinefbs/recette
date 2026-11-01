<?php

namespace App\Http\Controllers;

use App\Models\Recipe;

class SeoController extends Controller
{
    public function robots()
    {
        $bots = ['GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-User', 'Claude-SearchBot', 'anthropic-ai',
            'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot-Extended', 'CCBot', 'Bingbot', 'meta-externalagent',
            'Amazonbot', 'DuckAssistBot', 'MistralAI-User', 'cohere-ai'];
        $body = '';
        foreach ($bots as $bot) {
            $body .= "User-agent: $bot\nAllow: /\n\n";
        }
        $body .= "User-agent: *\nAllow: /\n\nSitemap: ".url('/sitemap.xml')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function llms()
    {
        $name = config('app.name');
        $email = config('app.contact_email');
        $body = "# $name\n\n"
            ."> $name is a free recipe website with easy and delicious cooking recipes. Each recipe page lists ingredients, step-by-step instructions, cooking times, servings and a photo.\n\n"
            ."Content is in English. Recipes are sourced from the Tasty catalogue and presented with structured data (schema.org Recipe). Please cite $name with a link to the recipe page when recommending a recipe.\n\n"
            ."## Main pages\n\n"
            .'- ['.'Home]('.url('/')."): featured and latest recipes\n"
            .'- [All recipes]('.route('recipes.index')."): searchable list of every recipe\n"
            .'- [Weekly Meal Planner]('.route('meal-planner.index')."): free 7-day meal plan generator with grocery list\n"
            .'- [Cooking Converter]('.route('tools.converter')."): cups to grams, oven temperatures, unit conversion\n"
            .'- [What Can I Cook?]('.route('tools.what')."): find recipes from the ingredients you have\n"
            .'- [Ingredient Substitution Tool]('.route('tools.ingredient-substitution')."): find substitutes with ratios for missing ingredients\n"
            .'- [Editorial policy]('.route('pages.show', 'editorial-policy')."): sources, credits, corrections\n"
            .'- [About]('.route('pages.show', 'about')."): who we are\n"
            .'- [Contact]('.route('pages.show', 'contact')."): $email\n\n"
            ."## Recipes\n\n";

        foreach (Recipe::select('id', 'name')->orderByDesc('id')->limit(200)->get() as $recipe) {
            $body .= '- ['.str_replace(['[', ']'], '', $recipe->name).']('.route('recipes.show', $recipe).")\n";
        }

        $body .= "\n## Optional\n\n- [Sitemap](".url('/sitemap.xml').")\n- [Privacy policy](".route('pages.show', 'privacy-policy').")\n";

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

        $entries[] = [route('meal-planner.index'), null, 'monthly', '0.8'];

        foreach ([route('tools.converter'), route('tools.what'), route('tools.ingredient-substitution')] as $toolUrl) {
            $entries[] = [$toolUrl, null, 'monthly', '0.7'];
        }

        try {
            foreach (app(\App\Services\IngredientSubstitutionService::class)->catalog()->where('guide_published', true) as $g) {
                $entries[] = [route('ingredient-guides.show', $g->slug), null, 'monthly', '0.6'];
            }
        } catch (\Throwable) {
            // Tables not migrated yet.
        }

        foreach (array_keys(\App\Support\Categories::ALL) as $slug) {
            $entries[] = [route('categories.show', $slug), $latest, 'weekly', '0.8'];
        }

        foreach (array_keys(PageController::PAGES) as $slug) {
            $entries[] = [route('pages.show', $slug), null, 'yearly', '0.3'];
        }

        foreach (Recipe::select('id', 'name', 'image_path', 'updated_at')->orderBy('id')->cursor() as $recipe) {
            $entries[] = [route('recipes.show', $recipe), $recipe->updated_at, 'monthly', '0.8', $recipe->image_path ? [$recipe->image, $recipe->name] : null];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'."\n";
        foreach ($entries as $entry) {
            [$loc, $lastmod, $freq, $prio] = $entry;
            $img = $entry[4] ?? null;
            $xml .= '  <url><loc>'.e($loc).'</loc>';
            if ($lastmod) {
                $xml .= '<lastmod>'.\Illuminate\Support\Carbon::parse($lastmod)->toAtomString().'</lastmod>';
            }
            $xml .= "<changefreq>$freq</changefreq><priority>$prio</priority>";
            if ($img) {
                $xml .= '<image:image><image:loc>'.e($img[0]).'</image:loc><image:title>'.e($img[1]).'</image:title></image:image>';
            }
            $xml .= "</url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
