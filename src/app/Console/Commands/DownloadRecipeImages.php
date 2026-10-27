<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\TastyService;
use Illuminate\Console\Command;

class DownloadRecipeImages extends Command
{
    protected $signature = 'recipes:download-images';

    protected $description = 'Télécharge dans public/images/recipes les images des recettes qui n\'en ont pas encore';

    public function handle(TastyService $tasty): int
    {
        Recipe::whereNull('image_path')->each(fn (Recipe $r) => $tasty->downloadImage($r));

        $this->info(Recipe::whereNotNull('image_path')->count().' / '.Recipe::count().' recettes avec image locale.');

        return self::SUCCESS;
    }
}
