<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Cancels an order and returns its units to stock.
 *
 * Guarded against double-cancellation: restocking twice would invent
 * inventory that does not exist on the shelf.
 */
class CancelOrder
{
    public function handle(Order $order, string $actor = 'Administration', ?int $userId = null): Order
    {
        if ($order->isCancelled()) {
            return $order;
        }

        return DB::transaction(function () use ($order, $actor, $userId) {
            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;   // product deleted since the sale; nothing to restock
                }

                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();

                if (! $product) {
                    continue;
                }

                $before = $product->stock;
                $product->increment('stock', $item->quantity);

                $product->stockMovements()->create([
                    'order_id' => $order->id,
                    'user_id' => $userId,
                    'delta' => $item->quantity,
                    'stock_before' => $before,
                    'stock_after' => $before + $item->quantity,
                    'reason' => 'Annulation '.$order->number,
                ]);
            }

            $order->update(['status' => 'annulee', 'cancelled_at' => now()]);
            $order->recordEvent('Commande annulée', $actor, $userId);

            return $order->fresh();
        });
    }
}
