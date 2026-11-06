<?php

use App\Http\Controllers\CookingGuideController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IngredientSubstitutionController;
use App\Http\Controllers\NutritionCalculatorController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SocialAdminController;
use App\Http\Controllers\WeeklyMealPlannerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::redirect('/index-4', '/', 301);
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/ads.txt', [SeoController::class, 'ads']);
Route::get('/llms.txt', [SeoController::class, 'llms']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/.well-known/mcp/server-card.json', [\App\Http\Controllers\McpController::class, 'card'])->name('mcp.card');
Route::post('/mcp', [\App\Http\Controllers\McpController::class, 'handle'])->middleware('throttle:60,1')->name('mcp.handle');
Route::get('/mcp', fn () => response('Use POST (MCP Streamable HTTP).', 405, ['Allow' => 'POST']));

Route::get('/{slug}', [PageController::class, 'show'])
    ->whereIn('slug', array_keys(PageController::PAGES))
    ->name('pages.show');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::post('/recipes/import', [RecipeController::class, 'import'])->name('recipes.import');
Route::get('/recipes/feed', [RecipeController::class, 'feed'])->middleware('throttle:30,1')->name('recipes.feed');
Route::get('/tools/cooking-converter', [\App\Http\Controllers\ToolController::class, 'converter'])->name('tools.converter');
Route::get('/tools/nutrition-calculator', [NutritionCalculatorController::class, 'index'])->name('tools.nutrition-calculator');
Route::get('/tools/nutrition-calculator/search', [NutritionCalculatorController::class, 'search'])->middleware('throttle:60,1')->name('tools.nutrition-calculator.search');
Route::post('/tools/nutrition-calculator/calculate', [NutritionCalculatorController::class, 'calculate'])->middleware('throttle:60,1')->name('tools.nutrition-calculator.calculate');
Route::get('/what-can-i-cook', [\App\Http\Controllers\ToolController::class, 'whatCanICook'])->name('tools.what');

Route::get('/tools/ingredient-substitution', [IngredientSubstitutionController::class, 'index'])->name('tools.ingredient-substitution');
Route::post('/tools/ingredient-substitution/search', [IngredientSubstitutionController::class, 'search'])
    ->middleware('throttle:60,1')->name('tools.ingredient-substitution.search');
Route::get('/ingredient-substitutes/{slug}', [IngredientSubstitutionController::class, 'guide'])
    ->where('slug', '[a-z0-9-]+')->name('ingredient-guides.show');

Route::prefix('cooking-tips')->name('cooking-tips.')->group(function () {
    Route::get('/', [CookingGuideController::class, 'index'])->name('index');
    Route::get('/search', [CookingGuideController::class, 'search'])->middleware('throttle:60,1')->name('search');
    Route::get('/{category}', [CookingGuideController::class, 'category'])->name('category');
    Route::get('/{category}/{guide}', [CookingGuideController::class, 'show'])->name('show');
});

Route::prefix('weekly-meal-planner')->name('meal-planner.')->group(function () {
    Route::get('/', [WeeklyMealPlannerController::class, 'index'])->name('index');
    Route::middleware('throttle:60,1')->group(function () {
        Route::post('/generate', [WeeklyMealPlannerController::class, 'generate'])->name('generate');
        Route::post('/replace-meal', [WeeklyMealPlannerController::class, 'replaceMeal'])->name('replace');
        Route::post('/shopping-list', [WeeklyMealPlannerController::class, 'shoppingList'])->name('shopping-list');
    });
});
Route::prefix('social-admin')->name('social.')->group(function () {
    Route::get('/login', [SocialAdminController::class, 'loginForm'])->name('login');
    Route::post('/login', [SocialAdminController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');

    Route::middleware('auth')->group(function () {
        Route::get('/', [SocialAdminController::class, 'index'])->name('index');
        Route::post('/logout', [SocialAdminController::class, 'logout'])->name('logout');
        Route::post('/recipes/fetch', [SocialAdminController::class, 'fetchRecipes'])->middleware('throttle:30,1')->name('recipes.fetch');
        Route::post('/recipes/{recipe}/facebook', [SocialAdminController::class, 'publishFacebook'])->whereNumber('recipe')->middleware('throttle:20,1')->name('facebook.publish');
        Route::post('/recipes/{recipe}/facebook/schedule', [SocialAdminController::class, 'scheduleFacebook'])->whereNumber('recipe')->middleware('throttle:20,1')->name('facebook.schedule');
        Route::post('/recipes/{recipe}/facebook/sync', [SocialAdminController::class, 'syncFacebook'])->whereNumber('recipe')->middleware('throttle:20,1')->name('facebook.sync');
        Route::post('/recipes/{recipe}/facebook/toggle', [SocialAdminController::class, 'toggleFacebook'])->whereNumber('recipe')->name('facebook.toggle');
    });
});

Route::get('/category/{slug}', [RecipeController::class, 'categoryPage'])->whereIn('slug', array_keys(\App\Support\Categories::ALL))->name('categories.show');
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->whereNumber('recipe')->name('recipes.show');
