<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Str;

/**
 * Delivery rules, in one place.
 *
 * Casablanca ships free, same day when the order lands before the cut-off.
 * Everywhere else pays a flat standard rate, waived once the basket reaches the
 * free-shipping threshold, with express as a paid 24h upgrade.
 *
 * Pricing charges from this, the checkout labels read from it and the product
 * structured data is generated from it, so the number a customer is quoted, the
 * number they are charged and the number Google is told cannot drift apart.
 */
final class Shipping
{
    /** Same-day city. Matched loosely: the picker is free text on other forms. */
    public const SAME_DAY_CITY = 'Casablanca';

    public static function isSameDayCity(?string $city): bool
    {
        if (blank($city)) {
            return false;
        }

        // Accent- and case-insensitive: "casablanca", "CASABLANCA", "Casa".
        return Str::contains(
            Str::lower(Str::ascii($city)),
            ['casablanca', 'casa']
        );
    }

    /** Hour of day, 24h, after which a Casablanca order ships the next day. */
    public static function sameDayCutoffHour(): int
    {
        return Setting::int('shipping_same_day_cutoff_hour', 20);
    }

    public static function standardCents(): int
    {
        return Setting::int('shipping_standard_cents', 2500);
    }

    public static function expressCents(): int
    {
        return Setting::int('shipping_express_cents', 3500);
    }

    public static function sameDayCityCents(): int
    {
        return Setting::int('shipping_casablanca_cents', 0);
    }

    public static function freeThresholdCents(): int
    {
        return Setting::int('free_shipping_threshold_cents', 60000);
    }

    /**
     * What delivery costs, in centimes.
     *
     * @param  int  $netCents  Subtotal after discounts — the threshold is judged
     *                         on what the customer actually pays, not list price.
     */
    public static function costFor(int $netCents, string $method, ?string $city = null): int
    {
        if ($netCents <= 0) {
            return 0;
        }

        // Casablanca is free whichever method is picked. Express is sold as a
        // 24h upgrade for other cities; in a city already served same-day it
        // would be charging for nothing.
        if (static::isSameDayCity($city)) {
            return static::sameDayCityCents();
        }

        // Express stays a paid upgrade above the threshold: the free-shipping
        // promise is about standard delivery, not about a faster courier.
        if ($method === 'express') {
            return static::expressCents();
        }

        return $netCents >= static::freeThresholdCents() ? 0 : static::standardCents();
    }

    /**
     * The delivery choices to show for a city, priced and with their promise.
     *
     * @return array<int, array{value: string, label: string, delay: string, cents: int|null, note: string|null}>
     */
    public static function optionsFor(?string $city, int $netCents = 0): array
    {
        if (static::isSameDayCity($city)) {
            return [[
                'value' => 'standard',
                'label' => 'Livraison à Casablanca',
                'delay' => 'Le jour même si vous commandez avant '.static::sameDayCutoffHour().' h',
                'cents' => static::sameDayCityCents(),
                'note' => null,
            ]];
        }

        return [
            [
                'value' => 'standard',
                'label' => 'Livraison standard',
                'delay' => '2 à 3 jours',
                'cents' => $netCents >= static::freeThresholdCents() ? 0 : static::standardCents(),
                'note' => 'Offerte dès '.mad(static::freeThresholdCents()),
            ],
            [
                'value' => 'express',
                'label' => 'Livraison express',
                'delay' => '24 h',
                'cents' => static::expressCents(),
                'note' => null,
            ],
        ];
    }

    /**
     * OfferShippingDetails for a product's structured data.
     *
     * Two entries, because the rate genuinely differs by destination: Google
     * shows "Livraison gratuite" against Casablanca and the real rate against
     * the rest of the country, instead of guessing from one flat number.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function structuredData(): array
    {
        $shipping = fn (int $cents, string $region, int $minDays, int $maxDays) => [
            '@type' => 'OfferShippingDetails',
            'shippingRate' => [
                '@type' => 'MonetaryAmount',
                'value' => number_format($cents / 100, 2, '.', ''),
                'currency' => 'MAD',
            ],
            'shippingDestination' => [
                '@type' => 'DefinedRegion',
                'addressCountry' => 'MA',
                'addressRegion' => $region,
            ],
            'deliveryTime' => [
                '@type' => 'ShippingDeliveryTime',
                'handlingTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => 0,
                    'maxValue' => $minDays === 0 ? 0 : 1,
                    'unitCode' => 'DAY',
                ],
                'transitTime' => [
                    '@type' => 'QuantitativeValue',
                    'minValue' => $minDays,
                    'maxValue' => $maxDays,
                    'unitCode' => 'DAY',
                ],
            ],
        ];

        return [
            $shipping(static::sameDayCityCents(), static::SAME_DAY_CITY, 0, 0),
            $shipping(static::standardCents(), 'Maroc', 2, 3),
        ];
    }

    /** One line summarising the offer, for pages that are not the checkout. */
    public static function summary(): string
    {
        return 'Livraison offerte à Casablanca (le jour même avant '
            .static::sameDayCutoffHour().' h) · '
            .mad(static::standardCents()).' ailleurs en 2 à 3 jours, offerte dès '
            .mad(static::freeThresholdCents());
    }
}
