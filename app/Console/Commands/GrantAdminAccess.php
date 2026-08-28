<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantAdminAccess extends Command
{
    protected $signature = 'admin:grant {email : Email of the existing user}';

    protected $description = 'Grant an existing user access to the Filament admin panel';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("User with email {$email} was not found.");

            return self::FAILURE;
        }

        $user->is_admin = true;
        $user->save();

        $this->info("Admin access granted to {$email}.");

        return self::SUCCESS;
    }
}
