@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name)
@section('description', $product->meta_description ?: strip_tags($product->short ?? ''))
@section('seoType', 'product')
@section('seoImage', $product->primaryImageUrl())
@section('seoImageAlt', $product->name)

{{-- Price and stock in the share card itself, which is what a Facebook or
     Instagram shop catalogue reads off the page. --}}
@section('ogTags')
<meta property="product:price:amount" content="{{ number_format($product->effectivePriceCents() / 100, 2, '.', '') }}" />
<meta property="product:price:currency" content="MAD" />
<meta property="product:availability" content="{{ $product->stock > 0 ? 'in stock' : 'out of stock' }}" />
@if ($product->brand)
<meta property="product:brand" content="{{ $product->brand }}" />
@endif
@if ($product->gtin)
<meta property="product:retailer_item_id" content="{{ $product->gtin }}" />
@endif
@endsection

@section('content')
  {{-- Product structured data: what gets a rich result in Google.
       Built in a php block rather than the json directive, because Blade parses
       a bare '@context' key inside a directive argument as a directive itself. --}}
  @php
      // array_filter throughout: a null gtin13 or a null aggregateRating is a
      // structured-data error, where an absent key is simply an absent key.
      $jsonLd = json_encode(array_filter([
          '@context' => 'https://schema.org',
          '@type' => 'Product',
          'name' => $product->name,
          'sku' => $product->sku,
          'gtin13' => $product->gtin,
          'brand' => ['@type' => 'Brand', 'name' => $product->brand],
          'description' => strip_tags($product->short ?? ''),
          'image' => $product->images->map(fn ($i) => $i->url())->all(),
          // Ratings are the difference between a plain blue link and a row of
          // stars in the results. Omitted entirely when there are none:
          // an aggregateRating of 0 out of 0 is a structured-data error.
          'aggregateRating' => $product->reviews_count > 0 ? [
              '@type' => 'AggregateRating',
              'ratingValue' => (string) round($product->rating_avg, 1),
              'reviewCount' => (int) $product->reviews_count,
          ] : null,
          'offers' => array_filter([
              '@type' => 'Offer',
              'url' => route('product', $product),
              'priceCurrency' => 'MAD',
              'price' => number_format($product->effectivePriceCents() / 100, 2, '.', ''),
              'availability' => $product->stock > 0
                  ? 'https://schema.org/InStock'
                  : 'https://schema.org/OutOfStock',
              'availableDeliveryMethod' => 'https://schema.org/OnSitePickup',
              // Cash on delivery is the whole payment model here, and saying so
              // in the markup is what lets Google show it as a shop feature.
              'acceptedPaymentMethod' => [
                  '@type' => 'PaymentMethod',
                  'name' => 'Paiement à la livraison',
              ],
              // Generated from App\Support\Shipping, so the delivery Google is
              // told about cannot drift from the one the checkout charges.
              'shippingDetails' => \App\Support\Shipping::structuredData(),
          ]),
      ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  @endphp
  <script type="application/ld+json">{!! $jsonLd !!}</script>

  <nav class="sc-wrap" style="max-width:1320px;margin:0 auto;padding:26px 40px 0;font-size:11.5px;color:#9B9B9B">
    <a href="{{ route('home') }}" style="color:#9B9B9B">Accueil</a> ·
    <a href="{{ route('shop', ['categorie' => $product->category->slug]) }}" style="color:#9B9B9B">{{ $product->category->name }}</a>
  </nav>

  <section class="sc-wrap sc-stack" style="max-width:1320px;margin:0 auto;padding:26px 40px 74px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:start">
    {{-- Gallery --}}
    <div x-data="{ active: 0 }">
      <div style="background:#FAFAFA;aspect-ratio:1;display:flex;align-items:center;justify-content:center;padding:40px">
        @foreach ($product->images as $index => $image)
          <div x-show="active === {{ $index }}" x-cloak
               style="width:100%;height:100%;background-image:url('{{ $image->url() }}');background-repeat:no-repeat;background-position:center;background-size:contain;mix-blend-mode:multiply"></div>
        @endforeach
      </div>

      @if ($product->images->count() > 1)
        <div style="display:flex;gap:1px;margin-top:1px;background:#E6E6E6">
          @foreach ($product->images as $index => $image)
            <button type="button" @click="active = {{ $index }}"
                    :style="`background:#FAFAFA;border:0;padding:14px;cursor:pointer;width:86px;height:86px;outline:${active === {{ $index }} ? '1px solid #14120F' : 'none'};outline-offset:-1px`">
              <span style="display:block;width:100%;height:100%;background-image:url('{{ $image->url() }}');background-repeat:no-repeat;background-position:center;background-size:contain;mix-blend-mode:multiply"></span>
            </button>
          @endforeach
        </div>
      @endif
    </div>

    {{-- Details --}}
    <div>
      <div style="font-size:9.5px;letter-spacing:0.18em;text-transform:uppercase;color:#9B9B9B;margin-bottom:12px">
        <a href="{{ route('shop', ['marque' => $product->brand]) }}" style="color:#9B9B9B">{{ $product->brand }}</a>
        @if ($product->gamme)
          · <a href="{{ route('shop', ['gamme' => $product->gamme]) }}" style="color:#9B9B9B">{{ $product->gamme }}</a>
        @endif
      </div>
      <h1 class="sc-h1" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:32px;line-height:1.2;margin:0 0 16px">{{ $product->name }}</h1>

      @if ($product->reviews_count > 0)
        <div style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:#6B6B6B;margin-bottom:18px">
          <span style="color:#14120F">★</span><span>{{ $product->rating_avg }}</span><span style="opacity:0.5">({{ $product->reviews_count }} avis)</span>
        </div>
      @endif

      <div style="display:flex;align-items:baseline;gap:12px;margin-bottom:26px">
        <span style="font-size:26px">{{ mad($product->effectivePriceCents()) }}</span>
        @if ($product->isOnSale())
          <span style="font-size:16px;color:#9B9B9B;text-decoration:line-through">{{ mad($product->price_cents) }}</span>
          <span style="background:#14120F;color:#FFFFFF;font-size:9.5px;letter-spacing:0.14em;padding:5px 8px;text-transform:uppercase">−{{ $product->discountPercent() }}%</span>
        @endif
      </div>

      @if ($product->short)
        <p style="margin:0 0 26px;color:#454545;font-size:15px;line-height:1.65">{{ $product->short }}</p>
      @endif

      <div style="margin-bottom:26px">
        @livewire('add-to-cart', ['product' => $product, 'withStepper' => true, 'withStockNote' => true])
      </div>

      <div style="border-top:1px solid #E6E6E6;padding-top:20px;display:grid;gap:10px;font-size:13px;color:#6B6B6B">
        <div>Paiement à la livraison partout au Maroc</div>
        <div>Livraison offerte dès {{ mad($freeShippingThreshold) }}</div>
        <div>Produit authentique · Distributeur agréé</div>
      </div>

      @if ($product->bullets)
        <div style="margin-top:32px">
          <h2 style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin:0 0 14px">Bénéfices</h2>
          <ul style="margin:0;padding-left:18px;display:grid;gap:9px;font-size:14px;color:#454545">
            @foreach ($product->bullets as $bullet)
              <li>{{ $bullet }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      @if ($product->actifs)
        <div style="margin-top:32px">
          <h2 style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin:0 0 14px">Actifs clés</h2>
          <div style="display:grid;gap:16px">
            @foreach ($product->actifs as $actif)
              <div style="border-left:2px solid #E6E6E6;padding-left:16px">
                <div style="font-size:14px;margin-bottom:5px">{{ $actif['t'] ?? '' }}</div>
                <p style="margin:0;font-size:13.5px;color:#6B6B6B;line-height:1.6">{{ $actif['d'] ?? '' }}</p>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </div>
  </section>

  @if ($reviews->isNotEmpty())
  <section class="sc-wrap" style="max-width:1320px;margin:0 auto;padding:0 40px 74px">
    <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:28px;margin:0 0 24px">Avis vérifiés</h2>
    <div class="sc-grid-2" style="display:grid;grid-template-columns:repeat(2, 1fr);gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
      @foreach ($reviews as $review)
        <figure style="background:#FFFFFF;padding:26px;margin:0">
          <div style="color:#14120F;margin-bottom:10px;font-size:13px">{{ str_repeat('★', $review->rating) }}<span style="color:#CFC7BA">{{ str_repeat('★', 5 - $review->rating) }}</span></div>
          <blockquote style="margin:0 0 12px;font-size:14px;line-height:1.6;color:#454545">{{ $review->body }}</blockquote>
          <figcaption style="font-size:11.5px;color:#9B9B9B">{{ $review->author }}@if ($review->verified) · <span style="color:#3F6B45">Achat vérifié</span>@endif</figcaption>
        </figure>
      @endforeach
    </div>
  </section>
  @endif

  @if ($related->isNotEmpty())
  <section class="sc-wrap" style="max-width:1320px;margin:0 auto;padding:0 40px 74px">
    <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:28px;margin:0 0 24px">À associer</h2>
    <div class="sc-products sc-grid-4" style="display:grid;grid-template-columns:repeat(4, 1fr);gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
      @foreach ($related as $item)
        @include('partials.product-card', ['product' => $item])
      @endforeach
    </div>
  </section>
  @endif
@endsection
