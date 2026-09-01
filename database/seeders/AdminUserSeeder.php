<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@skinchemists.ma');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => env('ADMIN_NAME', 'Administrateur'),
                // Only set on creation: re-seeding must never reset a password
                // the owner has since changed.
                'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            ]
        );

        $user->forceFill(['is_admin' => true])->save();

        if ($user->wasRecentlyCreated) {
            $this->command->warn("Admin créé : {$email} — changez le mot de passe avant la mise en ligne.");
        }
    }
}
