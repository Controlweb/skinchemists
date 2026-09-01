<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeAdmin extends Command
{
    protected $signature = 'admin:make
                            {email : Adresse email du membre de l\'équipe}
                            {--name= : Nom affiché (nouveau compte uniquement)}
                            {--password= : Mot de passe (nouveau compte uniquement)}
                            {--revoke : Retirer l\'accès au back-office}';

    protected $description = "Créer un compte d'administration ou accorder l'accès à un compte existant";

    public function handle(): int
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if ($this->option('revoke')) {
            if (! $user) {
                $this->error("Aucun compte pour {$email}.");

                return self::FAILURE;
            }

            $user->forceFill(['is_admin' => false])->save();
            $this->info("Accès retiré pour {$email}.");

            return self::SUCCESS;
        }

        if (! $user) {
            $password = $this->option('password') ?: $this->secret('Mot de passe');

            if (! $password) {
                $this->error('Un mot de passe est requis pour créer un compte.');

                return self::FAILURE;
            }

            $user = User::create([
                'name' => $this->option('name') ?: str($email)->before('@')->headline(),
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->info("Compte créé : {$email}");
        }

        // Never touches the password of an existing account: this command is
        // also the recovery path when someone was seeded before is_admin
        // existed, and it must not silently reset their credentials.
        $user->forceFill(['is_admin' => true])->save();

        $this->info("Accès au back-office accordé à {$email}.");

        return self::SUCCESS;
    }
}
