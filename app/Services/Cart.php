<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Session-backed cart. Stores product ids and quantities only — never prices
 * or names, so a tampered session cannot produce a tampered total.
 */
class Cart
{
    private const KEY = 'cart';

    /** @var Collection<int, array{product: Product, quantity: int}>|null */
    private ?Collection $lines = null;

    public function add(Product $product, int $quantity = 1): void
    {
        $items = $this->items();
        $current = $items[$product->id] ?? 0;

        $this->write($items, $product->id, $this->clamp($product, $current + $quantity));
    }

    public function setQuantity(Product $product, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($product->id);

            return;
        }

        $this->write($this->items(), $product->id, $this->clamp($product, $quantity));
    }

    public function remove(int $productId): void
    {
        $items = $this->items();
        unset($items[$productId]);
        session()->put(self::KEY, $items);
        $this->lines = null;
    }

    public function clear(): void
    {
        session()->forget(self::KEY);
        $this->lines = null;
    }

    /** @return Collection<int, array{product: Product, quantity: int}> */
    public function lines(): Collection
    {
        if ($this->lines !== null) {
            return $this->lines;
        }

        $items = $this->items();

        if ($items === []) {
            return $this->lines = collect();
        }

        $products = Product::with('images')
            ->active()
            ->whereIn('id', array_keys($items))
            ->get()
            ->keyBy('id');

        // A product that went inactive or was deleted since it was added
        // silently drops out rather than breaking checkout.
        return $this->lines = collect($items)
            ->filter(fn ($quantity, $id) => $products->has($id))
            ->map(fn ($quantity, $id) => [
                'product' => $products[$id],
                'quantity' => min($quantity, max($products[$id]->stock, 0)),
            ])
            ->filter(fn ($line) => $line['quantity'] > 0)
            ->values();
    }

    public function isEmpty(): bool
    {
        return $this->lines()->isEmpty();
    }

    /** Total number of units, for the header badge. */
    public function count(): int
    {
        return (int) $this->lines()->sum('quantity');
    }

    /** @return array<int, int> productId => quantity */
    private function items(): array
    {
        return array_map('intval', session()->get(self::KEY, []));
    }

    private function write(array $items, int $productId, int $quantity): void
    {
        $items[$productId] = $quantity;
        session()->put(self::KEY, $items);
        $this->lines = null;
    }

    private function clamp(Product $product, int $quantity): int
    {
        return max(1, min($quantity, max($product->stock, 1)));
    }
}
