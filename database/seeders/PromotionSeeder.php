<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            ['code' => 'MAROC10', 'name' => 'Bienvenue Maroc', 'type' => 'percent', 'value' => 10, 'min_subtotal_cents' => 0, 'is_active' => true],
            ['code' => 'LIVRAISONOFFERTE', 'name' => 'Livraison offerte', 'type' => 'free_shipping', 'value' => 0, 'min_subtotal_cents' => 40000, 'is_active' => true],
            ['code' => 'PREMIERE', 'name' => 'Première commande', 'type' => 'fixed', 'value' => 5000, 'min_subtotal_cents' => 0, 'is_active' => true],
        ];

        foreach ($promotions as $promotion) {
            Promotion::updateOrCreate(['code' => $promotion['code']], $promotion);
        }
    }
}
