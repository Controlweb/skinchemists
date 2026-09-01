@php
    $navLink = 'background:none;border:0;padding:0;cursor:pointer;color:#14120F;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;font-weight:500;text-decoration:none';
@endphp

<div style="background:#14120F;color:#FFFFFF;display:flex;align-items:center;justify-content:center;gap:28px;padding:9px 20px;font-size:10.5px;letter-spacing:0.18em;text-transform:uppercase;font-weight:500">
  <span>Livraison offerte dès {{ mad($freeShippingThreshold) }}</span>
  <span>Paiement à la livraison partout au Maroc</span>
  <span>Distributeur agréé</span>
</div>

<header style="position:sticky;top:0;z-index:40;background:#FFFFFF;border-bottom:1px solid #E6E6E6"
        x-data="{ mega: false, search: false }"
        @mouseleave="mega = false">
  <div style="max-width:1320px;margin:0 auto;padding:0 40px;height:90px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:24px">
    <nav style="display:flex;align-items:center;gap:26px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;font-weight:500">
      <a href="{{ route('shop') }}" style="{{ $navLink }}">Soins</a>
      <button type="button" @mouseenter="mega = true" @click="mega = !mega" style="{{ $navLink }}">Actifs</button>
      <a href="{{ route('shop', ['tri' => 'populaires']) }}" style="{{ $navLink }}">Best-sellers</a>
      <a href="{{ route('bundles') }}" style="{{ $navLink }}">Coffrets</a>
      <a href="{{ route('lab') }}" style="{{ $navLink }}">Le Lab</a>
    </nav>

    <a href="{{ route('home') }}" style="display:block;line-height:0">
      <img src="{{ asset('uploads/black_Logo_1.webp') }}" alt="skinChemists" style="height:44px;width:auto;display:block" />
    </a>

    <div style="display:flex;align-items:center;justify-content:flex-end;gap:20px">
      <button type="button" @click="search = !search" aria-label="Rechercher" style="background:none;border:0;cursor:pointer;padding:0;display:flex;color:#14120F">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="11" cy="11" r="7"></circle><line x1="16.5" y1="16.5" x2="21" y2="21"></line></svg>
      </button>
      <span style="font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:#6B6B6B">FR · MAD</span>
      @livewire('cart-button')
    </div>
  </div>

  {{-- Mega menu: hover-driven, no server round-trip.
       Absolutely positioned against the sticky header so it overlays the page
       instead of growing the header and pushing the content down. --}}
  <div x-show="mega" x-cloak
       style="position:absolute;top:100%;left:0;right:0;z-index:50;border-top:1px solid #E6E6E6;background:#FFFFFF;box-shadow:0 18px 40px -18px rgba(20,18,15,0.28);animation:scmIn 0.18s ease both">
    <div style="max-width:1320px;margin:0 auto;padding:34px 40px 40px;display:grid;grid-template-columns:1.1fr 1.1fr 1fr;gap:48px">
      <div>
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:16px">Par actif</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px">
          @foreach ($navIngredients as $ing => $ingSlug)
            <a href="{{ $ingSlug ? route('ingredient', $ingSlug) : route('shop', ['actif' => $ing]) }}"
               style="color:#14120F;font-size:14px">{{ $ing }}</a>
          @endforeach
        </div>
      </div>
      <div>
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:16px">Par préoccupation</div>
        <div style="display:grid;gap:10px">
          @foreach ($navConcerns as $concern)
            <a href="{{ route('shop', ['besoin' => $concern]) }}" style="color:#14120F;font-size:14px">{{ $concern }}</a>
          @endforeach
        </div>
      </div>
      <div style="border-left:1px solid #E6E6E6;padding-left:40px">
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:16px">Nouveau</div>
        <div style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:26px;line-height:1.15;margin-bottom:10px">Édition limitée Caviar</div>
        <p style="margin:0 0 16px;color:#6B6B6B;font-size:13.5px">Jour et nuit, la gamme la plus concentrée du laboratoire.</p>
        <a href="{{ route('shop', ['actif' => 'Caviar']) }}" style="display:inline-block;background:#14120F;color:#FFFFFF;border:0;padding:12px 22px;cursor:pointer;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">Découvrir</a>
      </div>
    </div>
  </div>

  {{-- Search: a plain GET form to the shop. Shareable URL, works without JS. --}}
  <div x-show="search" x-cloak style="position:fixed;inset:0;z-index:60;background:rgba(20,18,15,0.4)" @click.self="search = false">
    <form method="GET" action="{{ route('shop') }}" style="background:#FAFAFA;padding:30px 40px 36px;animation:scmIn 0.2s ease both">
      <div style="max-width:900px;margin:0 auto">
        <div style="display:flex;align-items:center;gap:16px;border-bottom:1px solid #14120F;padding-bottom:12px">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#14120F" stroke-width="1.4"><circle cx="11" cy="11" r="7"></circle><line x1="16.5" y1="16.5" x2="21" y2="21"></line></svg>
          <input name="q" value="{{ request('q') }}" x-ref="q" placeholder="Rechercher un produit, un actif…" style="flex:1;border:0;background:none;outline:none;font-size:22px;font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;color:#14120F" />
          <button type="button" @click="search = false" style="background:none;border:0;cursor:pointer;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#6B6B6B">Fermer</button>
        </div>
      </div>
    </form>
  </div>
</header>
