<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateSocialAdmin extends Command
{
    protected $signature = 'social:create-admin {email} {--name=Admin}';

    protected $description = 'Create or update the login for the /social-admin area (prompts for the password)';

    public function handle(): int
    {
        $password = $this->secret('Password (min 12 characters)');
        if (strlen((string) $password) < 12) {
            $this->error('Password too short.');

            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $this->argument('email')],
            ['name' => $this->option('name'), 'password' => Hash::make($password)]
        );
        $this->info('Admin saved.');

        return self::SUCCESS;
    }
}
