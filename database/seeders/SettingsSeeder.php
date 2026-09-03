<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'store_name' => 'skinChemists Maroc',
            'store_email' => 'contact@skinchemists.ma',
            'store_phone' => '+212662403006',
            'free_shipping_threshold_cents' => 60000,   // 600 MAD
            'shipping_standard_cents' => 3500,          // 35 MAD
            'shipping_express_cents' => 6000,           // 60 MAD
            'low_stock_threshold' => 5,

            // SEO. seo_indexable is the one that matters on a staging copy:
            // turn it off there so it cannot compete with the real shop.
            'seo_site_name' => 'SkinChemists Maroc',
            'seo_title_suffix' => 'SkinChemists Maroc',
            'seo_default_title' => 'SkinChemists Maroc — Soins scientifiques',
            'seo_default_description' => 'SkinChemists Maroc : soins anti-âge formulés au Caviar, Rétinol, Acide Hyaluronique et Vitamine C. Livraison partout au Maroc, paiement à la livraison.',
            'seo_default_image' => 'uploads/black_Logo_1.webp',
            'seo_indexable' => 1,
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
