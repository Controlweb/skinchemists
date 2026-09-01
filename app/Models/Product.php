<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use Concerns\HasImages;

    /**
     * Set by PlaceOrder/CancelOrder, which write their own stock movements
     * with order context. Stops ProductObserver logging the same change twice.
     */
    public bool $suppressStockLog = false;

    protected $fillable = [
        'sku', 'gtin', 'name', 'slug', 'brand', 'category_id',
        'ingredient', 'concern', 'price_cents', 'sale_price_cents',
        'short', 'bullets', 'actifs', 'stock', 'low_stock_threshold',
        'is_active', 'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'bullets' => 'array',
            'actifs' => 'array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }


    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** The price actually charged: sale price when set, else list price. */
    public function effectivePriceCents(): int
    {
        return $this->sale_price_cents ?? $this->price_cents;
    }

    public function isOnSale(): bool
    {
        return $this->sale_price_cents !== null
            && $this->sale_price_cents < $this->price_cents;
    }

    public function discountPercent(): int
    {
        if (! $this->isOnSale() || $this->price_cents === 0) {
            return 0;
        }

        return (int) round(100 - ($this->sale_price_cents / $this->price_cents * 100));
    }

    public function isLowStock(): bool
    {
        return $this->stock > 0 && $this->stock <= $this->low_stock_threshold;
    }


    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
