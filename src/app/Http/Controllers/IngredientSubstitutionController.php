<?php

namespace App\Http\Controllers;

use App\Http\Requests\IngredientSubstitutionRequest;
use App\Services\IngredientSubstitutionService;

class IngredientSubstitutionController extends Controller
{
    public function __construct(private IngredientSubstitutionService $service) {}

    public function index()
    {
        return $this->render(null, []);
    }

    public function search(IngredientSubstitutionRequest $request)
    {
        $data = $request->validated();
        $qty = isset($data['quantity']) ? (float) $data['quantity'] : null;
        $result = $this->service->search($data['ingredient'], $data['purpose'], $data['diet'], $qty, $data['unit'] ?? null);

        return $this->render($result, $data);
    }

    public function guide(string $slug)
    {
        $ingredient = $this->service->findBySlug($slug);
        abort_unless($ingredient && $ingredient->guide_published, 404);

        $site = config('app.name');
        $name = $ingredient->name;
        $title = "$name Substitutes: Best Ratios & Swaps";
        $description = "Out of ".mb_strtolower($name)."? Compare the best substitutes with ratios, what changes in taste and texture, and dietary notes.";
        $canonical = route('ingredient-guides.show', $ingredient->slug);

        return view('tools.ingredient-substitution.guide', [
            'site' => $site,
            'pageTitle' => "Substitutes for $name",
            'ingredient' => $ingredient,
            'items' => $ingredient->substitutes->sortByDesc('quality_score')->values(),
            'guides' => $this->service->catalog()->where('guide_published', true)->where('id', '!=', $ingredient->id),
            'seo' => [
                'title' => "$title | $site",
                'description' => $description,
                'canonical' => $canonical,
                'type' => 'article',
                'schema' => [
                    [
                        '@context' => 'https://schema.org', '@type' => 'Article',
                        'headline' => $title, 'description' => $description,
                        'mainEntityOfPage' => $canonical,
                        'dateModified' => optional($ingredient->updated_at)->toIso8601String(),
                        'author' => ['@type' => 'Organization', 'name' => $site],
                        'publisher' => ['@type' => 'Organization', 'name' => $site, 'url' => url('/')],
                    ],
                    $this->breadcrumbs([
                        ['Ingredient Substitution Tool', route('tools.ingredient-substitution')],
                        ["Substitutes for $name", $canonical],
                    ]),
                ],
            ],
        ]);
    }

    private function render(?array $result, array $input)
    {
        $site = config('app.name');
        $canonical = route('tools.ingredient-substitution');

        return view('tools.ingredient-substitution.index', [
            'site' => $site,
            'pageTitle' => 'Ingredient Substitution Tool',
            'catalog' => $this->service->catalog(),
            'result' => $result,
            'input' => $input + ['ingredient' => '', 'purpose' => 'general', 'diet' => 'none', 'quantity' => '', 'unit' => 'g'],
            'seo' => [
                'title' => 'Ingredient Substitution Tool – Find Easy Cooking Alternatives',
                'description' => 'Missing an ingredient? Find the best cooking and baking substitutes with helpful ratios, alternatives and tips.',
                'canonical' => $canonical,
                'robots' => $result !== null ? 'noindex,follow' : null,
                'schema' => [
                    [
                        '@context' => 'https://schema.org', '@type' => 'WebApplication',
                        'name' => 'Ingredient Substitution Tool', 'url' => $canonical,
                        'description' => 'Find cooking and baking substitutes with ratios, tips and dietary filters.',
                        'applicationCategory' => 'LifestyleApplication', 'operatingSystem' => 'Any',
                        'browserRequirements' => 'Requires a web browser',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                    $this->breadcrumbs([['Ingredient Substitution Tool', $canonical]]),
                ],
            ],
        ]);
    }

    private function breadcrumbs(array $trail): array
    {
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')]];
        foreach ($trail as $i => [$name, $url]) {
            $items[] = ['@type' => 'ListItem', 'position' => $i + 2, 'name' => $name, 'item' => $url];
        }

        return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
}
