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
            'store_phone' => '+212 5 22 00 00 00',
            'free_shipping_threshold_cents' => 60000,   // 600 MAD
            'shipping_standard_cents' => 3500,          // 35 MAD
            'shipping_express_cents' => 6000,           // 60 MAD
            'low_stock_threshold' => 5,
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
