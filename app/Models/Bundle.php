<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Collection;

class Bundle extends Model
{
    use Concerns\HasImages;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)
            ->withPivot('position')
            ->orderBy('position');
    }

    /**
     * What the coffret page shows: its own gallery if it has one, otherwise the
     * component product shots composed side by side, which is how the prototype
     * displayed it and stays correct with no work from staff.
     *
     * @return \Illuminate\Support\Collection<int, Image>
     */
    public function galleryImages(): Collection
    {
        if ($this->images->isNotEmpty()) {
            return $this->images;
        }

        return $this->products
            ->map(fn (Product $product) => $product->primaryImage())
            ->filter()
            ->values();
    }

    /** Sum of the components at their own current prices. */
    public function fullPriceCents(): int
    {
        return $this->products->sum(fn (Product $p) => $p->effectivePriceCents());
    }

    /**
     * Rounded to the nearest 5 MAD, matching the prototype's pricing.
     * This is the price the customer actually pays — Pricing applies the
     * difference as a discount when every component is in the cart.
     */
    public function priceCents(): int
    {
        $discounted = $this->fullPriceCents() * (100 - $this->discount_percent) / 100;

        return (int) (round($discounted / 500) * 500);
    }

    public function savingCents(): int
    {
        return max(0, $this->fullPriceCents() - $this->priceCents());
    }

    /** A bundle can only be sold as often as its scarcest component allows. */
    public function availableQuantity(): int
    {
        if ($this->products->isEmpty()) {
            return 0;
        }

        return max(0, (int) $this->products->min('stock'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
