<?php

namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
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

    private function flush(): void
    {
        Cache::forget('nav:ingredients');
        Cache::forget('nav:concerns');
    }
}
