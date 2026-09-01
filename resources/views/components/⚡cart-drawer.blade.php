<?php

use App\Models\Product;
use App\Services\Cart;
use App\Support\Pricing;
use App\Models\Setting;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    public bool $open = false;

    #[On('open-cart-drawer')]
    public function openDrawer(): void
    {
        $this->open = true;
    }

    public function close(): void
    {
        $this->open = false;
    }

    public function setQuantity(int $productId, int $quantity, Cart $cart): void
    {
        $product = Product::find($productId);

        if ($product) {
            $cart->setQuantity($product, $quantity);
            $this->dispatch('cart-updated');
        }
    }

    public function remove(int $productId, Cart $cart): void
    {
        $cart->remove($productId);
        $this->dispatch('cart-updated');
    }

    public function with(Cart $cart): array
    {
        $lines = $cart->lines();

        return [
            'lines' => $lines,
            'pricing' => Pricing::for($lines, null, 'standard'),
            'freeShippingThreshold' => Setting::int('free_shipping_threshold_cents', 60000),
        ];
    }
};
?>

<div>
    @if ($open)
        <div style="position:fixed;inset:0;z-index:70;display:flex;justify-content:flex-end">
            <div wire:click="close" style="position:absolute;inset:0;background:rgba(20,18,15,0.35)"></div>

            <aside style="position:relative;width:426px;max-width:92vw;background:#FFFFFF;height:100%;display:flex;flex-direction:column;animation:scmDrawer 0.22s ease both">
                <div style="display:flex;align-items:center;justify-content:space-between;padding:24px 26px;border-bottom:1px solid #E6E6E6">
                    <span style="font-size:11px;letter-spacing:0.18em;text-transform:uppercase">Panier ({{ $lines->sum('quantity') }})</span>
                    <button type="button" wire:click="close" aria-label="Fermer"
                            style="background:none;border:0;cursor:pointer;font-size:18px;line-height:1;color:#14120F">×</button>
                </div>

                @if ($pricing->subtotal > 0 && $pricing->subtotal < $freeShippingThreshold)
                    <div style="padding:14px 26px;border-bottom:1px solid #E6E6E6;font-size:12.5px;color:#6B6B6B">
                        Plus que <strong style="color:#14120F">{{ mad($freeShippingThreshold - $pricing->subtotal) }}</strong> pour la livraison offerte.
                    </div>
                @endif

                <div style="flex:1;overflow:auto;padding:0 26px">
                    @forelse ($lines as $line)
                        @php($product = $line['product'])
                        <div wire:key="line-{{ $product->id }}" style="display:flex;gap:16px;padding:20px 0;border-bottom:1px solid #F4F4F4">
                            <a href="{{ route('product', $product) }}" style="width:72px;height:72px;flex:none;background:#FAFAFA;background-image:url('{{ asset($product->primaryImage()) }}');background-repeat:no-repeat;background-position:center;background-size:contain"></a>
                            <div style="flex:1;min-width:0">
                                <a href="{{ route('product', $product) }}" style="display:block;font-size:13px;color:#14120F;line-height:1.35;margin-bottom:6px">{{ $product->name }}</a>
                                <div style="font-size:13px;color:#6B6B6B;margin-bottom:10px">{{ mad($product->effectivePriceCents()) }}</div>
                                <div style="display:flex;align-items:center;gap:14px">
                                    <div style="display:flex;align-items:center;border:1px solid #E6E6E6">
                                        <button type="button" wire:click="setQuantity({{ $product->id }}, {{ $line['quantity'] - 1 }})" style="background:none;border:0;cursor:pointer;padding:2px 10px;color:#14120F">−</button>
                                        <span style="min-width:22px;text-align:center;font-size:13px">{{ $line['quantity'] }}</span>
                                        <button type="button" wire:click="setQuantity({{ $product->id }}, {{ $line['quantity'] + 1 }})" style="background:none;border:0;cursor:pointer;padding:2px 10px;color:#14120F">+</button>
                                    </div>
                                    <button type="button" wire:click="remove({{ $product->id }})" style="background:none;border:0;cursor:pointer;font-size:11.5px;color:#9B9B9B;text-decoration:underline">Retirer</button>
                                </div>
                            </div>
                            <div style="font-size:13px;color:#14120F">{{ mad($product->effectivePriceCents() * $line['quantity']) }}</div>
                        </div>
                    @empty
                        <p style="padding:40px 0;text-align:center;color:#9B9B9B;font-size:13.5px">Votre panier est vide.</p>
                    @endforelse
                </div>

                @if ($lines->isNotEmpty())
                    <div style="border-top:1px solid #E6E6E6;padding:22px 26px">
                        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:8px">
                            <span style="color:#6B6B6B">Sous-total</span>
                            <span>{{ mad($pricing->subtotal) }}</span>
                        </div>
                        @if ($pricing->discount > 0)
                            <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:8px;color:#3F6B45">
                                <span>Remise coffret</span>
                                <span>−{{ mad($pricing->discount) }}</span>
                            </div>
                        @endif
                        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:18px">
                            <span style="color:#6B6B6B">Livraison</span>
                            <span>{{ $pricing->shipping === 0 ? 'Offerte' : mad($pricing->shipping) }}</span>
                        </div>
                        <a href="{{ route('checkout') }}"
                           style="display:block;text-align:center;background:#14120F;color:#FFFFFF;padding:15px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">
                            Commander — {{ mad($pricing->total) }}
                        </a>
                        <a href="{{ route('cart') }}" style="display:block;text-align:center;margin-top:12px;font-size:12px;color:#6B6B6B;text-decoration:underline">Voir le panier</a>
                    </div>
                @endif
            </aside>
        </div>
    @endif
</div>
