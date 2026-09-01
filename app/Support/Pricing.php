<?php

namespace App\Support;

use App\Models\Promotion;
use App\Models\Setting;

/**
 * The single source of truth for order money.
 *
 * Both the cart page and PlaceOrder call this. If totals ever need to change,
 * they change here once — never in two places that can drift apart.
 */
final readonly class Pricing
{
    public function __construct(
        public int $subtotal,
        public int $discount,
        public int $shipping,
        public int $total,
    ) {}

    /**
     * @param  iterable<array{product: \App\Models\Product, quantity: int}>  $lines
     */
    public static function for(iterable $lines, ?Promotion $promotion, string $shippingMethod): self
    {
        $subtotal = 0;

        foreach ($lines as $line) {
            $subtotal += $line['product']->effectivePriceCents() * $line['quantity'];
        }

        $discount = $promotion?->discountFor($subtotal) ?? 0;
        $net = $subtotal - $discount;

        $shipping = self::shippingFor($net, $shippingMethod);

        // ponytail: a free-shipping coupon only waives standard delivery.
        // Express is a paid upgrade; waiving it is a business call nobody has made.
        if ($shippingMethod !== 'express' && $promotion?->grantsFreeShipping($subtotal)) {
            $shipping = 0;
        }

        return new self($subtotal, $discount, $shipping, $net + $shipping);
    }

    private static function shippingFor(int $netCents, string $shippingMethod): int
    {
        if ($netCents <= 0) {
            return 0;
        }

        if ($shippingMethod === 'express') {
            return Setting::int('shipping_express_cents', 6000);
        }

        $threshold = Setting::int('free_shipping_threshold_cents', 60000);

        return $netCents >= $threshold
            ? 0
            : Setting::int('shipping_standard_cents', 3500);
    }
}
