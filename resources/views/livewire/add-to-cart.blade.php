<?php

use App\Models\Product;
use App\Services\Cart;
use Livewire\Component;

new class extends Component
{
    public Product $product;

    public int $quantity = 1;

    /** Show a quantity stepper on the product page, not on grid cards. */
    public bool $withStepper = false;

    /**
     * The low-stock line renders below the button, so on a grid card it pushed
     * that card's button out of line with its neighbours. Cards render the
     * warning themselves, under the rating, where it stays aligned.
     */
    public bool $withStockNote = false;

    public function increment(): void
    {
        $this->quantity = min($this->quantity + 1, max($this->product->stock, 1));
    }

    public function decrement(): void
    {
        $this->quantity = max(1, $this->quantity - 1);
    }

    public function add(Cart $cart): void
    {
        // Re-read stock: the page may have been open for a while.
        $this->product->refresh();

        if ($this->product->stock < 1) {
            $this->dispatch('cart-updated');

            return;
        }

        $cart->add($this->product, $this->quantity);

        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
    }
}; ?>

<div>
    @if ($product->stock < 1)
        <button type="button" disabled
                style="width:100%;background:#F4F4F4;color:#9B9B9B;border:0;padding:15px 22px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500;cursor:not-allowed">
            Rupture de stock
        </button>
    @else
        <div style="display:flex;gap:10px;align-items:stretch">
            @if ($withStepper)
                <div style="display:flex;align-items:center;border:1px solid #E6E6E6">
                    <button type="button" wire:click="decrement" aria-label="Diminuer"
                            style="background:none;border:0;cursor:pointer;padding:0 14px;font-size:16px;color:#14120F">−</button>
                    <span style="min-width:28px;text-align:center;font-size:14px">{{ $quantity }}</span>
                    <button type="button" wire:click="increment" aria-label="Augmenter"
                            style="background:none;border:0;cursor:pointer;padding:0 14px;font-size:16px;color:#14120F">+</button>
                </div>
            @endif

            <button type="button" wire:click="add" wire:loading.attr="disabled"
                    style="flex:1;background:#14120F;color:#FFFFFF;border:0;padding:15px 22px;cursor:pointer;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">
                <span wire:loading.remove wire:target="add">Ajouter au panier</span>
                <span wire:loading wire:target="add">Ajout…</span>
            </button>
        </div>

        @if ($withStockNote && $product->isLowStock())
            <p style="margin:10px 0 0;font-size:12px;color:#8A6A22">Plus que {{ $product->stock }} en stock.</p>
        @endif
    @endif
</div>
