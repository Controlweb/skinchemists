<?php

use App\Services\Cart;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function mount(Cart $cart): void
    {
        $this->count = $cart->count();
    }

    #[On('cart-updated')]
    public function refreshCount(Cart $cart): void
    {
        $this->count = $cart->count();
    }

    public function openDrawer(): void
    {
        $this->dispatch('open-cart-drawer');
    }
}; ?>

<div style="display:flex;align-items:center;gap:20px">
    <button type="button" wire:click="openDrawer"
            aria-label="Ouvrir le panier"
            style="background:none;border:0;cursor:pointer;padding:0;display:flex;align-items:center;gap:6px;color:#14120F;font-size:11px;letter-spacing:0.08em">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="M6 8h12l-1 12H7L6 8z"></path><path d="M9 8V6a3 3 0 0 1 6 0v2"></path></svg>
        <span style="min-width:14px;text-align:left">{{ $count }}</span>
    </button>
</div>
