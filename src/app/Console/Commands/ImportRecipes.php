<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\TastyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportRecipes extends Command
{
    protected $signature = 'recipes:import {--fresh : Vide la table et le dossier d\'images avant l\'import} {--q= : Mot-clé de recherche} {--batches=1 : Nombre de lots de 40}';

    protected $description = 'Importe les recettes depuis l\'API Tasty avec images stockées localement';

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

        $this->info("$total recette(s) importée(s) ; ".Recipe::whereNotNull('image_path')->count().' / '.Recipe::count().' avec image locale.');

        return self::SUCCESS;
    }
}
