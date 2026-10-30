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
            'title' => "Cooking recipes".($page > 1 && ! $filtered ? " – page $page" : '')." | $site",
            'description' => 'Browse our easy cooking recipes: photos, ingredients, detailed steps and preparation times. Find your next recipe.',
            'canonical' => $canonical,
            // Search result pages are low-value duplicates: do not index them
            'robots' => $filtered ? 'noindex,follow' : null,
            'schema' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Recipes', 'item' => route('recipes.index')],
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
            ?: "{$recipe->name} recipe: {$ingredients->count()} ingredients, {$steps->count()} steps".($recipe->total_time_minutes ? ", ready in {$recipe->total_time_minutes} min." : '.');

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
            'prepTime' => $recipe->prep_time_minutes ? 'PT'.$recipe->prep_time_minutes.'M' : null,
            'cookTime' => $recipe->cook_time_minutes ? 'PT'.$recipe->cook_time_minutes.'M' : null,
            'recipeYield' => $recipe->num_servings ? $recipe->num_servings.' servings' : null,
            'nutrition' => $this->nutrition($recipe->nutrition),
            'recipeCategory' => $this->category($recipe->name),
            'recipeCuisine' => 'International',
            'keywords' => $this->keywords($recipe->name),
            'recipeIngredient' => $ingredients->all(),
            'recipeInstructions' => $steps->map(fn ($t, $i) => [
                '@type' => 'HowToStep',
                'name' => 'Step '.($i + 1),
                'text' => $t,
                'url' => route('recipes.show', $recipe).'#step-'.($i + 1),
                'image' => $recipe->image,
            ])->all(),
        ]);

        $seo = [
            'title' => Str::limit($recipe->name, 38, '').' – Recipe | '.$site,
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
                        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                        ['@type' => 'ListItem', 'position' => 2, 'name' => 'Recipes', 'item' => route('recipes.index')],
                        ['@type' => 'ListItem', 'position' => 3, 'name' => $recipe->name, 'item' => route('recipes.show', $recipe)],
                    ],
                ],
            ],
        ];

        return view('recipes.show', ['recipe' => $recipe->toArray() + ['image' => $recipe->image], 'seo' => $seo]);
    }

    private function nutrition(?array $n): ?array
    {
        if (! $n) {
            return null;
        }
        $map = [
            'calories' => ['calories', ' kcal'],
            'carbohydrates' => ['carbohydrateContent', ' g'],
            'fat' => ['fatContent', ' g'],
            'fiber' => ['fiberContent', ' g'],
            'protein' => ['proteinContent', ' g'],
            'sugar' => ['sugarContent', ' g'],
        ];
        $out = ['@type' => 'NutritionInformation'];
        foreach ($map as $key => [$prop, $unit]) {
            if (isset($n[$key])) {
                $out[$prop] = $n[$key].$unit;
            }
        }

        return count($out) > 1 ? $out : null;
    }

    private function category(string $name): string
    {
        $map = [
            'Dessert' => ['cake', 'cookie', 'brownie', 'pie', 'pudding', 'muffin', 'ice cream', 'chocolate', 'cheesecake', 'cupcake', 'bread', 'tart', 'fudge', 'candy'],
            'Breakfast' => ['pancake', 'waffle', 'omelet', 'omelette', 'breakfast', 'oatmeal', 'granola', 'french toast', 'egg'],
            'Salad' => ['salad', 'slaw'],
            'Soup' => ['soup', 'stew', 'chili', 'broth'],
            'Appetizer' => ['dip', 'appetizer', 'wings', 'bites', 'snack', 'chutney', 'sauce'],
            'Drink' => ['smoothie', 'cocktail', 'lemonade', 'drink', 'tea', 'coffee'],
        ];
        $lower = strtolower($name);
        foreach ($map as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($lower, $word)) {
                    return $category;
                }
            }
        }

        return 'Main course';
    }

    private function keywords(string $name): string
    {
        return collect(preg_split('/[^a-z0-9]+/i', strtolower($name)))
            ->filter(fn ($w) => strlen($w) > 2)->push('recipe')->unique()->implode(', ');
    }

    public function feed(Request $request, TastyService $tasty)
    {
        $expected = (string) config('services.tasty.feed_token');
        abort_unless($expected !== '' && hash_equals($expected, (string) $request->query('token', '')), 403);

        try {
            $count = $request->boolean('refresh')
                ? $tasty->refresh()
                : $tasty->import(trim((string) $request->query('q', '')));
        } catch (RequestException $e) {
            return response()->json(['ok' => false, 'error' => 'Tasty API error'], $e->response->status() === 429 ? 429 : 502);
        }

        return response()->json(['ok' => true, 'imported' => $count, 'total' => Recipe::count()]);
    }

    public function import(Request $request, TastyService $tasty)
    {
        $q = trim((string) $request->input('q', ''));

        try {
            $count = $tasty->import($q);
        } catch (RequestException $e) {
            $msg = $e->response->status() === 429
                ? 'API quota reached, please try again later.'
                : 'Unable to fetch recipes at the moment.';

            return redirect()->route('recipes.index')->with('error', $msg);
        }

        return redirect()->route('recipes.index')->with('success', "$count recipe(s) fetched and saved.");
    }
}
