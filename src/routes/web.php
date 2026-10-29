<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::redirect('/index-4', '/', 301);
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/ads.txt', [SeoController::class, 'ads']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);

Route::get('/{slug}', [PageController::class, 'show'])
    ->whereIn('slug', array_keys(PageController::PAGES))
    ->name('pages.show');

Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::post('/recipes/import', [RecipeController::class, 'import'])->name('recipes.import');
Route::get('/recipes/feed', [RecipeController::class, 'feed'])->middleware('throttle:30,1')->name('recipes.feed');
Route::get('/recipes/{recipe}', [RecipeController::class, 'show'])->whereNumber('recipe')->name('recipes.show');
