<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Services\TastyService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $recipes = Recipe::query()
            ->when($q !== '', fn ($query) => $query->where('name', 'like', '%'.$q.'%'))
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $site = config('app.name');
        $filtered = $q !== '';
        $page = $recipes->currentPage();
        $canonical = ! $filtered && $page > 1 ? route('recipes.index', ['page' => $page]) : route('recipes.index');

        $seo = [
            'title' => "Recettes de cuisine".($page > 1 && ! $filtered ? " – page $page" : '')." | $site",
            'description' => 'Parcourez nos recettes de cuisine faciles : photos, ingrédients, étapes détaillées et temps de préparation. Trouvez votre prochaine recette.',
            'canonical' => $canonical,
            // Les pages de recherche sont des doublons à faible valeur : on ne les indexe pas
            'robots' => $filtered ? 'noindex,follow' : null,
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Recettes', 'item' => route('recipes.index')],
                    ],
                ],
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'ItemList',
                    'itemListElement' => $recipes->values()->map(fn ($r, $i) => [
                        '@type' => 'ListItem',
                        'position' => $recipes->firstItem() + $i,
                        'url' => route('recipes.show', $r),
                        'name' => $r->name,
                    ])->all(),
                ],
            ],
        ];

        return view('recipes.index', ['recipes' => $recipes, 'q' => $q, 'seo' => $seo]);
    }

    public function show(Recipe $recipe)
    {
        $site = config('app.name');
        $ingredients = collect($recipe->sections ?? [])
            ->flatMap(fn ($s) => collect($s['components'] ?? [])->pluck('raw_text'))
            ->filter()->values();
        $steps = collect($recipe->instructions ?? [])->pluck('display_text')->filter()->values();
        $description = trim(strip_tags((string) $recipe->description))
            ?: "Recette {$recipe->name} : {$ingredients->count()} ingrédients, {$steps->count()} étapes".($recipe->total_time_minutes ? ", prête en {$recipe->total_time_minutes} min." : '.');

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe->name,
            'image' => [$recipe->image],
            'description' => Str::limit($description, 300),
            'datePublished' => $recipe->created_at?->toIso8601String(),
            'dateModified' => $recipe->updated_at?->toIso8601String(),
            'author' => ['@type' => 'Organization', 'name' => $site],
            'totalTime' => $recipe->total_time_minutes ? 'PT'.$recipe->total_time_minutes.'M' : null,
            'recipeYield' => $recipe->num_servings ? $recipe->num_servings.' portions' : null,
            'recipeIngredient' => $ingredients->all(),
            'recipeInstructions' => $steps->map(fn ($t) => ['@type' => 'HowToStep', 'text' => $t])->all(),
        ]);

        $seo = [
            'title' => Str::limit($recipe->name, 50, '').' – Recette | '.$site,
            'description' => Str::limit($description, 155),
            'canonical' => route('recipes.show', $recipe),
            'type' => 'article',
            'image' => $recipe->image,
            'schema' => [
                $schema,
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Accueil', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Recettes', 'item' => route('recipes.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $recipe->name, 'item' => route('recipes.show', $recipe)],
                    ],
                ],
            ],
        ];

        return view('recipes.show', ['recipe' => $recipe->toArray() + ['image' => $recipe->image], 'seo' => $seo]);
    }

    public function import(Request $request, TastyService $tasty)
    {
        $q = trim((string) $request->input('q', ''));

        try {
            $count = $tasty->import($q);
        } catch (RequestException $e) {
            $msg = $e->response->status() === 429
                ? 'Quota de l\'API atteint, réessayez plus tard.'
                : 'Impossible de récupérer les recettes pour le moment.';

            return redirect()->route('recipes.index')->with('error', $msg);
        }

        return redirect()->route('recipes.index')->with('success', "$count recette(s) récupérée(s) et enregistrée(s).");
    }
}
