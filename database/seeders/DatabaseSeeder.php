<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Real data only — safe to run in production.
     * Demo reviews and sample orders live in DemoSeeder, run explicitly.
     */
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            CatalogSeeder::class,
            PromotionSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
