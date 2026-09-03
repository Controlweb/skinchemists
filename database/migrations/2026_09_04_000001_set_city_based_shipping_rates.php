<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Move to city-based delivery.
 *
 * Casablanca free and same day before the cut-off; 25 MAD elsewhere in 2-3
 * days, waived from 600 MAD; express 35 MAD in 24h outside Casablanca.
 *
 * A migration rather than the seeder: SettingsSeeder uses firstOrCreate, so it
 * only ever fills a missing row and every existing install would have kept the
 * old 35/60 MAD rates. Setting::put is used so the forever-cache is cleared
 * with the write — a raw update would leave the old prices being served.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'shipping_casablanca_cents' => 0,
            'shipping_standard_cents' => 2500,
            'shipping_express_cents' => 3500,
            'shipping_same_day_cutoff_hour' => 20,
            'free_shipping_threshold_cents' => 60000,
        ] as $key => $value) {
            Setting::put($key, $value);
        }
    }

    public function down(): void
    {
        Setting::put('shipping_standard_cents', 3500);
        Setting::put('shipping_express_cents', 6000);
    }
};
