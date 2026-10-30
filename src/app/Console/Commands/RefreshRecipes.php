<?php

namespace App\Console\Commands;

use App\Services\TastyService;
use Illuminate\Console\Command;

class RefreshRecipes extends Command
{
    protected $signature = 'recipes:refresh';

    protected $description = 'Re-fetch stored recipes from the API to fill in prep/cook times and nutrition';

    public function handle(TastyService $tasty): int
    {
        $this->info('Updated '.$tasty->refresh().' recipes.');

        return self::SUCCESS;
    }
}
