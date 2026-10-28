<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\TastyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportRecipes extends Command
{
    protected $signature = 'recipes:import {--fresh : Empties the table and the images folder before importing} {--q= : Search keyword} {--batches=1 : Number of batches of 40}';

    protected $description = 'Imports recipes from the Tasty API with locally stored images';

    public function handle(TastyService $tasty): int
    {
        if ($this->option('fresh')) {
            Recipe::truncate();
            File::cleanDirectory(public_path('images/recipes'));
        }

        $total = 0;
        for ($i = 0; $i < (int) $this->option('batches'); $i++) {
            $total += $tasty->import((string) $this->option('q'));
        }

        $this->info("$total recipe(s) imported; ".Recipe::whereNotNull('image_path')->count().' / '.Recipe::count().' with a local image.');

        return self::SUCCESS;
    }
}
