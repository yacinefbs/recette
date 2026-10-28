<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class TastyService
{
    public const BATCH_SIZE = 40;

    /** Fetches a batch of recipes from the API and saves them to the database. Returns the number of recipes processed. */
    public function import(string $query = ''): int
    {
        // Without a search term, each import continues where the previous one stopped
        $params = ['from' => $query === '' ? Recipe::count() : 0, 'size' => self::BATCH_SIZE];
        if ($query !== '') {
            $params['q'] = $query;
        }

        $results = Http::baseUrl('https://'.config('services.tasty.host'))
            ->withHeaders([
                'x-rapidapi-key' => config('services.tasty.key'),
                'x-rapidapi-host' => config('services.tasty.host'),
            ])
            ->timeout(20)
            ->get('/recipes/list', $params)
            ->throw()
            ->json('results', []);

        $count = 0;
        foreach ($results as $r) {
            // Entries without instructions (e.g. compilations) are not recipes
            if (empty($r['id']) || empty($r['name']) || empty($r['instructions'])) {
                continue;
            }

            $recipe = Recipe::updateOrCreate(['tasty_id' => $r['id']], [
                'name' => $r['name'],
                'description' => $r['description'] ?? null,
                'thumbnail_url' => $r['thumbnail_url'] ?? null,
                'total_time_minutes' => $r['total_time_minutes'] ?? null,
                'num_servings' => $r['num_servings'] ?? null,
                'sections' => $r['sections'] ?? [],
                'instructions' => $r['instructions'],
            ]);
            $this->downloadImage($recipe);
            $count++;
        }

        return $count;
    }

    /** Downloads the image into public/images/recipes and stores its path. */
    public function downloadImage(Recipe $recipe): void
    {
        if (! $recipe->thumbnail_url || ($recipe->image_path && is_file(public_path($recipe->image_path)))) {
            return;
        }

        try {
            $response = Http::timeout(20)->get($recipe->thumbnail_url);
        } catch (\Throwable) {
            return;
        }

        $ext = strtolower(pathinfo(parse_url($recipe->thumbnail_url, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        if (! $response->successful() || ! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return;
        }

        File::ensureDirectoryExists(public_path('images/recipes'));
        $path = "images/recipes/{$recipe->tasty_id}.{$ext}";
        file_put_contents(public_path($path), $response->body());
        $recipe->update(['image_path' => $path]);
    }
}
