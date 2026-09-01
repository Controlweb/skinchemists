<?php

namespace App\Support;

use App\Models\Bundle;
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
        $quantities = [];

        foreach ($lines as $line) {
            $subtotal += $line['product']->effectivePriceCents() * $line['quantity'];
            $quantities[$line['product']->id] = ($quantities[$line['product']->id] ?? 0) + $line['quantity'];
        }

        // Bundle savings come off first, then any coupon applies to what is
        // left. Stacking the other way round can discount past the subtotal.
        $bundleDiscount = self::bundleDiscount($quantities);
        $couponDiscount = $promotion?->discountFor($subtotal - $bundleDiscount) ?? 0;

        $discount = min($subtotal, $bundleDiscount + $couponDiscount);
        $net = $subtotal - $discount;

        $shipping = self::shippingFor($net, $shippingMethod);

        // ponytail: a free-shipping coupon only waives standard delivery.
        // Express is a paid upgrade; waiving it is a business call nobody has made.
        if ($shippingMethod !== 'express' && $promotion?->grantsFreeShipping($subtotal)) {
            $shipping = 0;
        }

        return new self($subtotal, $discount, $shipping, $net + $shipping);
    }

    /**
     * A bundle's advertised saving, granted once per complete set present in
     * the cart. The prototype showed the saving on the coffret page but added
     * the components at full price; this makes the promise real.
     *
     * @param  array<int, int>  $quantities  productId => quantity
     */
    private static function bundleDiscount(array $quantities): int
    {
        if ($quantities === []) {
            return 0;
        }

        $bundles = Bundle::with('products')->active()->get();
        $discount = 0;

        foreach ($bundles as $bundle) {
            $components = $bundle->products;

            if ($components->isEmpty()) {
                continue;
            }

            // Complete sets the cart holds. A missing component counts as 0,
            // which makes the minimum 0 and grants nothing.
            $sets = (int) $components->min(fn ($product) => $quantities[$product->id] ?? 0);

            if ($sets > 0) {
                $discount += $bundle->savingCents() * $sets;
            }
        }

        return $discount;
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
