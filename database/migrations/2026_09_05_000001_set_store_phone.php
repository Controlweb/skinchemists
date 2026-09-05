<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Publish the real shop number: +212 662 403 006.
 *
 * A migration rather than the seeder, for the same reason as the shipping
 * rates: SettingsSeeder uses firstOrCreate, so every install seeded before
 * today still holds the `+212 5 22 00 00 00` placeholder. Setting::put clears
 * the forever-cache with the write, which a raw update would leave stale.
 *
 * The contact page and the LocalBusiness JSON-LD both read this one row, and
 * the WhatsApp link is derived from it by stripping non-digits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::put('store_phone', '+212662403006');
    }

    public function down(): void
    {
        Setting::put('store_phone', '+212 5 22 00 00 00');
    }
};
