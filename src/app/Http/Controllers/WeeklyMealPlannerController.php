<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateMealPlanRequest;
use App\Http\Requests\ReplaceMealRequest;
use App\Services\MealPlanner\MealPlanGenerator;
use App\Services\MealPlanner\MealReplacementService;
use App\Services\MealPlanner\ShoppingListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WeeklyMealPlannerController extends Controller
{
    public function index()
    {
        $site = config('app.name');
        $canonical = route('meal-planner.index');

        return view('meal-planner.index', [
            'site' => $site,
            'pageTitle' => 'Free Weekly Meal Planner',
            'wide' => true,
            'seo' => [
                'title' => "Free Weekly Meal Planner – Plan Your Meals in 7 Days | $site",
                'description' => "Plan your weekly meals with $site. Create personalized meal plans, discover delicious recipes and generate your grocery shopping list for free.",
                'canonical' => $canonical,
                'schema' => [
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'BreadcrumbList',
                        'itemListElement' => [
                            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => url('/')],
                            ['@type' => 'ListItem', 'position' => 2, 'name' => 'Weekly Meal Planner', 'item' => $canonical],
                        ],
                    ],
                    [
                        '@context' => 'https://schema.org',
                        '@type' => 'WebApplication',
                        'name' => 'Weekly Meal Planner',
                        'url' => $canonical,
                        'applicationCategory' => 'LifestyleApplication',
                        'operatingSystem' => 'Any',
                        'offers' => ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD'],
                    ],
                ],
            ],
        ]);
    }

    public function generate(GenerateMealPlanRequest $request, MealPlanGenerator $generator): JsonResponse
    {
        $prefs = $request->preferences();

        try {
            $plan = $generator->generate($prefs, $request->validated('locked') ?? [], (int) ($request->validated('seed') ?? random_int(1, 2147483647)));
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($plan + ['preferences' => $this->echo($request)]);
    }

    public function replaceMeal(ReplaceMealRequest $request, MealReplacementService $replacer): JsonResponse
    {
        try {
            $recipe = $replacer->replace(
                $request->preferences(),
                $request->validated('meal'),
                $request->validated('current_id'),
                $request->validated('used_ids') ?? [],
                (int) ($request->validated('seed') ?? random_int(1, 2147483647)),
            );
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['recipe' => $recipe]);
    }

    public function shoppingList(Request $request, ShoppingListService $service): JsonResponse
    {
        $data = $request->validate([
            'servings' => ['required', 'integer', 'between:1,12'],
            'recipe_ids' => ['required', 'array', 'min:1', 'max:60'],
            'recipe_ids.*' => ['integer', 'min:1'],
        ]);

        return response()->json(['groups' => $service->build($data['recipe_ids'], (int) $data['servings'])]);
    }

    private function echo(GenerateMealPlanRequest $request): array
    {
        return $request->safe()->only(['servings', 'diet', 'budget', 'meals', 'days', 'avoid', 'have']);
    }
}
