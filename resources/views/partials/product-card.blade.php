<article style="background:#FFFFFF;display:flex;flex-direction:column">
    <a href="{{ route('product', $product) }}"
       style="border:0;background:#FAFAFA;padding:26px;cursor:pointer;position:relative;aspect-ratio:1;display:flex;align-items:center;justify-content:center">
        <span style="flex:1;align-self:stretch;background-image:url('{{ $product->primaryImageUrl() }}');background-repeat:no-repeat;background-position:center;background-size:contain;mix-blend-mode:multiply"></span>

        @if ($product->isOnSale())
            <span style="position:absolute;top:14px;left:14px;background:#14120F;color:#FFFFFF;font-size:9.5px;letter-spacing:0.14em;padding:5px 8px;text-transform:uppercase">−{{ $product->discountPercent() }}%</span>
        @endif

        @if ($product->stock < 1)
            <span style="position:absolute;top:14px;right:14px;background:#FFFFFF;border:1px solid #14120F;color:#14120F;font-size:9.5px;letter-spacing:0.14em;padding:5px 8px;text-transform:uppercase">Rupture</span>
        @endif
    </a>

    <div style="padding:18px 20px 22px;display:flex;flex-direction:column;gap:8px;flex:1">
        <div style="font-size:9.5px;letter-spacing:0.18em;text-transform:uppercase;color:#9B9B9B">{{ $product->brand }}</div>

        <a href="{{ route('product', $product) }}"
           style="font-size:14.5px;line-height:1.35;color:#14120F;font-weight:400;min-height:40px">{{ $product->name }}</a>

        @if ($product->reviews_count > 0)
            <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:#6B6B6B">
                <span style="color:#14120F">★</span><span>{{ $product->rating_avg }}</span><span style="opacity:0.5">({{ $product->reviews_count }})</span>
            </div>
        @endif

        <div style="display:flex;align-items:baseline;gap:9px;margin-top:auto">
            <span style="font-size:16px">{{ mad($product->effectivePriceCents()) }}</span>
            @if ($product->isOnSale())
                <span style="font-size:13px;color:#9B9B9B;text-decoration:line-through">{{ mad($product->price_cents) }}</span>
            @endif
        </div>

        <div style="margin-top:8px">
            @livewire('add-to-cart', ['product' => $product], key('add-'.$product->id))
        </div>
    </div>
</article>
