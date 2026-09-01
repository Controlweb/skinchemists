<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function updated(Product $product): void
    {
        $this->logStockChange($product);
    }

    /**
     * The nav facets are derived from products, so any product write can
     * invalidate them. Cheaper to forget two keys than to reason about which
     * edits matter.
     */
    public function saved(Product $product): void
    {
        $this->flush();
    }

    public function deleted(Product $product): void
    {
        $this->flush();
    }

    /**
     * Any stock change made outside PlaceOrder/CancelOrder — an admin editing
     * the field, a restock, an import — still has to leave a movement behind,
     * or the stock ledger stops reconciling with the shelf.
     *
     * The order actions write their own movements with order context, and set
     * suppressStockLog so this does not double-log them.
     */
    private function logStockChange(Product $product): void
    {
        if ($product->suppressStockLog || ! $product->wasChanged('stock')) {
            return;
        }

        $before = (int) $product->getOriginal('stock');
        $after = (int) $product->stock;

        $product->stockMovements()->create([
            'user_id' => auth()->id(),
            'delta' => $after - $before,
            'stock_before' => $before,
            'stock_after' => $after,
            'reason' => 'Ajustement manuel',
        ]);
    }

    private function flush(): void
    {
        Cache::forget('nav:ingredients');
        Cache::forget('nav:concerns');
    }
}
