<?php

namespace App\Actions;

use App\Exceptions\OutOfStockException;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Support\Pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Turns a cart into an order.
 *
 * Everything here happens in one transaction, and every price and stock level
 * is re-read from the database under a row lock. Whatever the browser sent is
 * treated as a request, never as fact.
 */
class PlaceOrder
{
    /**
     * @param  array<int, int>  $quantities  productId => quantity
     * @param  array<string, mixed>  $customer
     *
     * @throws OutOfStockException
     */
    public function handle(
        array $quantities,
        array $customer,
        string $shippingMethod = 'standard',
        ?string $couponCode = null,
    ): Order {
        $quantities = array_filter($quantities, fn ($q) => $q > 0);

        if ($quantities === []) {
            throw new \InvalidArgumentException('Le panier est vide.');
        }

        return DB::transaction(function () use ($quantities, $customer, $shippingMethod, $couponCode) {
            // Lock the rows before reading stock, so two simultaneous checkouts
            // cannot both see the last unit as available.
            $products = Product::whereIn('id', array_keys($quantities))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $short = [];
            $lines = [];

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product || ! $product->is_active) {
                    continue;
                }

                if ($product->stock < $quantity) {
                    $short[] = $product->name;

                    continue;
                }

                $lines[] = ['product' => $product, 'quantity' => (int) $quantity];
            }

            if ($short !== []) {
                throw new OutOfStockException($short);
            }

            if ($lines === []) {
                throw new \InvalidArgumentException('Aucun produit disponible dans le panier.');
            }

            $promotion = $couponCode
                ? Promotion::whereRaw('UPPER(code) = ?', [strtoupper($couponCode)])->first()
                : null;

            // Prices come from the locked rows, not from the request.
            $pricing = Pricing::for($lines, $promotion, $shippingMethod);

            $order = Order::create([
                'number' => (string) Str::uuid(),   // replaced below, once the id exists
                'status' => 'nouvelle',
                'payment_method' => 'cod',
                'payment_status' => 'en_attente',
                'shipping_method' => $shippingMethod,
                'first_name' => $customer['first_name'],
                'last_name' => $customer['last_name'],
                'phone' => $customer['phone'],
                'email' => $customer['email'] ?? null,
                'address' => $customer['address'],
                'city' => $customer['city'],
                'zip' => $customer['zip'] ?? null,
                'subtotal_cents' => $pricing->subtotal,
                'discount_cents' => $pricing->discount,
                'shipping_cents' => $pricing->shipping,
                'total_cents' => $pricing->total,
                'coupon_code' => $pricing->discount > 0 || ($promotion?->grantsFreeShipping($pricing->subtotal) ?? false)
                    ? $promotion?->code
                    : null,
                'note' => $customer['note'] ?? null,
            ]);

            // Derived from the auto-increment id, so it is unique without a
            // read-then-write race on a separate counter.
            $order->update(['number' => 'SCM-'.(1042 + $order->id)]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $quantity = $line['quantity'];
                $unit = $product->effectivePriceCents();

                $order->items()->create([
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image_path' => $product->primaryImage(),
                    'unit_price_cents' => $unit,
                    'quantity' => $quantity,
                    'line_total_cents' => $unit * $quantity,
                ]);

                $before = $product->stock;
                $product->suppressStockLog = true;
                $product->decrement('stock', $quantity);

                $product->stockMovements()->create([
                    'order_id' => $order->id,
                    'delta' => -$quantity,
                    'stock_before' => $before,
                    'stock_after' => $before - $quantity,
                    'reason' => 'Commande '.$order->number,
                ]);
            }

            if ($promotion && $order->coupon_code) {
                $promotion->increment('uses');
            }

            $order->recordEvent('Commande reçue');

            return $order->fresh(['items']);
        });
    }
}
