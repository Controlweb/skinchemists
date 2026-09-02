<?php

use App\Models\Bundle;
use App\Services\Cart;
use Livewire\Component;

new class extends Component
{
    public Bundle $bundle;

    public function add(Cart $cart): void
    {
        $this->bundle->load('products');

        // Re-read stock: the page may have been open a while, and a coffret
        // is only sellable while every component is in stock.
        if ($this->bundle->availableQuantity() < 1) {
            $this->dispatch('cart-updated');

            return;
        }

        foreach ($this->bundle->products as $product) {
            $cart->add($product, 1);
        }

        $this->dispatch('cart-updated');
        $this->dispatch('open-cart-drawer');
    }
}; ?>

<div>
    @if ($bundle->availableQuantity() < 1)
        <button type="button" disabled
                style="background:#F4F4F4;color:#9B9B9B;border:0;padding:16px 30px;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500;cursor:not-allowed">
            Coffret indisponible
        </button>
    @else
        <button type="button" wire:click="add" wire:loading.attr="disabled"
                style="background:#14120F;color:#FFFFFF;border:0;padding:16px 30px;cursor:pointer;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">
            <span wire:loading.remove wire:target="add">Ajouter le coffret</span>
            <span wire:loading wire:target="add">Ajout…</span>
        </button>
    @endif
</div>
