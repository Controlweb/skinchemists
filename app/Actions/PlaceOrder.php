<?php

namespace App\Actions;

use App\Exceptions\OutOfStockException;
use App\Mail\NewOrderNotification;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Setting;
use App\Support\Pricing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

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

        $order = DB::transaction(function () use ($quantities, $customer, $shippingMethod, $couponCode) {
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

            // Prices come from the locked rows, not from the request. The city
            // does come from the request — it is what the customer chose, and
            // it decides whether delivery is free.
            $pricing = Pricing::for($lines, $promotion, $shippingMethod, $customer['city'] ?? null);

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
                    // Snapshot the raw path; the order view encodes it for display.
                    'image_path' => $product->primaryImage()?->path,
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

        // Only once the order is safely committed. Sending inside the
        // transaction would email a confirmation for an order a rollback
        // then erased.
        $this->sendNotifications($order);

        return $order;
    }

    /**
     * Mail goes out synchronously on shared hosting, so a slow or misconfigured
     * SMTP server must not turn a completed sale into an error page. The order
     * exists; a missing email is a support problem, not a lost order.
     */
    private function sendNotifications(Order $order): void
    {
        if ($order->email) {
            try {
                Mail::to($order->email)->send(new OrderConfirmation($order));
            } catch (Throwable $e) {
                Log::error("Confirmation email failed for {$order->number}: ".$e->getMessage());
            }
        }

        $storeEmail = Setting::get('store_email');

        if ($storeEmail) {
            try {
                Mail::to($storeEmail)->send(new NewOrderNotification($order));
            } catch (Throwable $e) {
                Log::error("Staff notification failed for {$order->number}: ".$e->getMessage());
            }
        }
    }
}
