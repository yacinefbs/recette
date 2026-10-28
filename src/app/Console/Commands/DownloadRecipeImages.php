<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\TastyService;
use Illuminate\Console\Command;

class DownloadRecipeImages extends Command
{
    protected $signature = 'recipes:download-images';

    protected $description = 'Downloads into public/images/recipes the images of recipes that don\'t have one yet';

    public function handle(TastyService $tasty): int
    {
        Recipe::whereNull('image_path')->each(fn (Recipe $r) => $tasty->downloadImage($r));

        $this->info(Recipe::whereNotNull('image_path')->count().' / '.Recipe::count().' recipes with a local image.');

        return self::SUCCESS;
    }
}
