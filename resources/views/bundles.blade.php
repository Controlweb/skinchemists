@extends('layouts.app')

@section('title', 'Coffrets & rituels — SkinChemists Maroc')
@section('description', 'Des protocoles complets assemblés par le laboratoire, à un prix inférieur à l\'achat séparé. Paiement à la livraison partout au Maroc.')

@section('content')
  <main style="max-width:1320px;margin:0 auto;padding:36px 40px 90px">
    <div style="font-size:11.5px;color:#6B6B6B;margin-bottom:26px">
      <a href="{{ route('home') }}" style="color:#6B6B6B">Accueil</a> / <span style="color:#14120F">Coffrets &amp; rituels</span>
    </div>

    <div style="border-bottom:1px solid #E6E6E6;padding-bottom:30px;margin-bottom:40px;display:grid;grid-template-columns:1.3fr 1fr;gap:60px;align-items:end">
      <div>
        <h1 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:46px;margin:0 0 12px">Coffrets &amp; rituels</h1>
        <p style="margin:0;color:#6B6B6B;font-size:15px;max-width:560px">Des protocoles complets assemblés par le laboratoire : chaque coffret associe les formules qui se potentialisent, à un prix inférieur à l'achat séparé.</p>
      </div>
      <div style="display:grid;gap:9px;font-size:13px;color:#454545;justify-items:end;text-align:right">
        <span>La remise est appliquée automatiquement au panier</span>
        <span>Stock du coffret limité par son composant le plus rare</span>
        <span>Livraison offerte dès {{ mad($freeShippingThreshold) }}</span>
      </div>
    </div>

    <div style="display:grid;gap:20px">
      @forelse ($bundles as $bundle)
        <article style="border:1px solid #E6E6E6;display:grid;grid-template-columns:1fr 1.2fr;background:#FFFFFF">
          <div style="background:#FAFAFA;display:flex;align-items:center;justify-content:center;gap:14px;padding:44px 36px;min-height:300px">
            @foreach ($bundle->products as $product)
              <span style="width:33%;height:200px;background-image:url('{{ asset($product->primaryImage()) }}');background-repeat:no-repeat;background-position:center;background-size:contain;mix-blend-mode:multiply"></span>
            @endforeach
          </div>

          <div style="padding:36px 38px;display:flex;flex-direction:column;gap:18px">
            <div>
              @if ($bundle->tag)
                <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:10px">{{ $bundle->tag }}</div>
              @endif
              <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:30px;margin:0 0 10px">{{ $bundle->name }}</h2>
              <p style="margin:0;color:#6B6B6B;font-size:14.5px;max-width:520px">{{ $bundle->blurb }}</p>
            </div>

            <div style="display:grid;gap:1px;background:#F0F0F0;border:1px solid #F0F0F0">
              @foreach ($bundle->products as $product)
                <a href="{{ route('product', $product) }}" style="background:#FFFFFF;display:grid;grid-template-columns:42px 1fr auto;gap:14px;align-items:center;padding:11px 14px;color:#14120F">
                  <span style="width:42px;height:42px;background-color:#FAFAFA;background-image:url('{{ asset($product->primaryImage()) }}');background-repeat:no-repeat;background-position:center;background-size:contain"></span>
                  <span style="font-size:13.5px;line-height:1.35">{{ $product->name }}</span>
                  <span style="font-size:13px;color:#9B9B9B">{{ mad($product->effectivePriceCents()) }}</span>
                </a>
              @endforeach
            </div>

            <div style="margin-top:auto;display:flex;align-items:flex-end;justify-content:space-between;gap:24px;flex-wrap:wrap">
              <div style="display:grid;gap:5px">
                <div style="display:flex;align-items:baseline;gap:11px">
                  <span style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.02em;font-size:28px">{{ mad($bundle->priceCents()) }}</span>
                  <span style="font-size:14px;color:#9B9B9B;text-decoration:line-through">{{ mad($bundle->fullPriceCents()) }}</span>
                </div>
                <span style="font-size:12px;color:oklch(0.48 0.09 250)">
                  Vous économisez {{ mad($bundle->savingCents()) }} ·
                  {{ $bundle->availableQuantity() === 0
                      ? 'Coffret indisponible'
                      : $bundle->availableQuantity().' coffret'.($bundle->availableQuantity() > 1 ? 's' : '').' disponible'.($bundle->availableQuantity() > 1 ? 's' : '') }}
                </span>
              </div>

              @livewire('add-bundle', ['bundle' => $bundle], key('bundle-'.$bundle->id))
            </div>
          </div>
        </article>
      @empty
        <p style="color:#9B9B9B;padding:40px 0">Aucun coffret disponible pour le moment.</p>
      @endforelse
    </div>
  </main>
@endsection
