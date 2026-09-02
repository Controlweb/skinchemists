@php
    $navLink = 'background:none;border:0;padding:0;cursor:pointer;color:#14120F;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;font-weight:500;text-decoration:none';
    $mobileLink = 'display:block;padding:16px 0;border-bottom:1px solid #E6E6E6;color:#14120F;font-size:15px;letter-spacing:0.04em';
@endphp

{{-- The three promises don't fit a phone in one row, so on mobile they rotate
     in place rather than wrapping into a three-line black block. --}}
<div style="background:#14120F;color:#FFFFFF;display:flex;align-items:center;justify-content:center;gap:28px;padding:9px 20px;font-size:10.5px;letter-spacing:0.18em;text-transform:uppercase;font-weight:500;text-align:center">
  <span class="sc-desktop-only">Livraison offerte dès {{ mad($freeShippingThreshold) }}</span>
  <span class="sc-desktop-only">Paiement à la livraison partout au Maroc</span>
  <span class="sc-desktop-only">Distributeur agréé</span>

  <span class="sc-mobile-only" style="width:100%"
        x-data="{ i: 0, msgs: ['Livraison offerte dès {{ mad($freeShippingThreshold) }}', 'Paiement à la livraison', 'Distributeur agréé'] }"
        x-init="setInterval(() => i = (i + 1) % msgs.length, 4000)">
    <span x-text="msgs[i]">Livraison offerte dès {{ mad($freeShippingThreshold) }}</span>
  </span>
</div>

<header style="position:sticky;top:0;z-index:40;background:#FFFFFF;border-bottom:1px solid #E6E6E6"
        x-data="{ mega: false, search: false, menu: false }"
        @mouseleave="mega = false"
        @keydown.escape.window="menu = false; search = false; mega = false">

  <div class="sc-wrap sc-header-bar" style="max-width:1320px;margin:0 auto;padding:0 40px;height:90px;display:grid;grid-template-columns:1fr auto 1fr;align-items:center;gap:24px">

    {{-- Desktop navigation --}}
    <nav class="sc-desktop-only" style="display:flex;align-items:center;gap:26px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;font-weight:500">
      <a href="{{ route('shop') }}" style="{{ $navLink }}">Soins</a>
      <button type="button" @mouseenter="mega = true" @click="mega = !mega" style="{{ $navLink }}">Actifs</button>
      <a href="{{ route('shop', ['tri' => 'populaires']) }}" style="{{ $navLink }}">Best-sellers</a>
      <a href="{{ route('bundles') }}" style="{{ $navLink }}">Coffrets</a>
      <a href="{{ route('lab') }}" style="{{ $navLink }}">Le Lab</a>
    </nav>

    {{-- Mobile menu trigger. 44px tall so it is a comfortable tap target. --}}
    <button type="button" class="sc-mobile-only" @click="menu = true" aria-label="Ouvrir le menu"
            style="background:none;border:0;padding:0;width:44px;height:44px;cursor:pointer;color:#14120F;align-items:center;justify-content:flex-start">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="3" y1="7" x2="21" y2="7"></line><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="17" x2="21" y2="17"></line></svg>
    </button>

    <a href="{{ route('home') }}" style="display:block;line-height:0">
      <img src="{{ asset('uploads/black_Logo_1.webp') }}" alt="skinChemists" class="sc-logo" style="height:44px;width:auto;display:block" />
    </a>

    <div style="display:flex;align-items:center;justify-content:flex-end;gap:20px">
      <button type="button" @click="search = !search" aria-label="Rechercher"
              style="background:none;border:0;cursor:pointer;padding:0;display:flex;color:#14120F">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><circle cx="11" cy="11" r="7"></circle><line x1="16.5" y1="16.5" x2="21" y2="21"></line></svg>
      </button>
      <span class="sc-desktop-only" style="font-size:10.5px;letter-spacing:0.14em;text-transform:uppercase;color:#6B6B6B">FR · MAD</span>
      @livewire('cart-button')
    </div>
  </div>

  {{-- Mega menu: hover-driven, desktop only.
       Absolutely positioned against the sticky header so it overlays the page
       instead of growing the header and pushing the content down. --}}
  <div x-show="mega" x-cloak class="sc-desktop-only"
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
    <form method="GET" action="{{ route('shop') }}" style="background:#FAFAFA;padding:24px 20px 28px;animation:scmIn 0.2s ease both">
      <div style="max-width:900px;margin:0 auto">
        <div style="display:flex;align-items:center;gap:12px;border-bottom:1px solid #14120F;padding-bottom:12px">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#14120F" stroke-width="1.4" style="flex:none"><circle cx="11" cy="11" r="7"></circle><line x1="16.5" y1="16.5" x2="21" y2="21"></line></svg>
          <input name="q" value="{{ request('q') }}" placeholder="Rechercher…"
                 style="flex:1;min-width:0;border:0;background:none;outline:none;font-size:18px;font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;color:#14120F" />
          <button type="button" @click="search = false" style="background:none;border:0;cursor:pointer;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#6B6B6B;flex:none">Fermer</button>
        </div>
      </div>
    </form>
  </div>

  {{-- Mobile menu: a full-height panel, since a phone has no room for a
       hover mega menu. Actives and concerns are collapsed behind toggles so
       the top-level choices stay reachable without scrolling. --}}
  <div x-show="menu" x-cloak class="sc-mobile-panel" style="position:fixed;inset:0;z-index:80">
    <div @click="menu = false" style="position:absolute;inset:0;background:rgba(20,18,15,0.35)"></div>

    <aside style="position:relative;width:86vw;max-width:360px;height:100%;background:#FFFFFF;display:flex;flex-direction:column;animation:scmDrawer 0.22s ease both">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:20px;border-bottom:1px solid #E6E6E6">
        <span style="font-size:11px;letter-spacing:0.18em;text-transform:uppercase">Menu</span>
        <button type="button" @click="menu = false" aria-label="Fermer"
                style="background:none;border:0;cursor:pointer;font-size:22px;line-height:1;color:#14120F;width:44px;height:44px">×</button>
      </div>

      <nav style="flex:1;overflow-y:auto;padding:8px 20px 28px" x-data="{ open: null }">
        <a href="{{ route('shop') }}" style="{{ $mobileLink }}">Tous les soins</a>

        <button type="button" @click="open = open === 'actifs' ? null : 'actifs'"
                style="{{ $mobileLink }}width:100%;text-align:left;background:none;border-width:0 0 1px 0;border-style:solid;border-color:#E6E6E6;cursor:pointer;display:flex;justify-content:space-between;align-items:center">
          <span>Actifs</span>
          <span x-text="open === 'actifs' ? '−' : '+'" style="color:#9B9B9B"></span>
        </button>
        <div x-show="open === 'actifs'" x-cloak style="padding:6px 0 6px 14px">
          @foreach ($navIngredients as $ing => $ingSlug)
            <a href="{{ $ingSlug ? route('ingredient', $ingSlug) : route('shop', ['actif' => $ing]) }}"
               style="display:block;padding:11px 0;color:#6B6B6B;font-size:14px">{{ $ing }}</a>
          @endforeach
        </div>

        <button type="button" @click="open = open === 'besoins' ? null : 'besoins'"
                style="{{ $mobileLink }}width:100%;text-align:left;background:none;border-width:0 0 1px 0;border-style:solid;border-color:#E6E6E6;cursor:pointer;display:flex;justify-content:space-between;align-items:center">
          <span>Préoccupations</span>
          <span x-text="open === 'besoins' ? '−' : '+'" style="color:#9B9B9B"></span>
        </button>
        <div x-show="open === 'besoins'" x-cloak style="padding:6px 0 6px 14px">
          @foreach ($navConcerns as $concern)
            <a href="{{ route('shop', ['besoin' => $concern]) }}"
               style="display:block;padding:11px 0;color:#6B6B6B;font-size:14px">{{ $concern }}</a>
          @endforeach
        </div>

        <a href="{{ route('shop', ['tri' => 'populaires']) }}" style="{{ $mobileLink }}">Best-sellers</a>
        <a href="{{ route('bundles') }}" style="{{ $mobileLink }}">Coffrets &amp; rituels</a>
        <a href="{{ route('lab') }}" style="{{ $mobileLink }}">Le Lab</a>
        <a href="{{ route('tracking') }}" style="{{ $mobileLink }}">Suivre ma commande</a>

        <div style="margin-top:24px;font-size:12px;color:#9B9B9B;line-height:1.7">
          Paiement à la livraison<br>
          Livraison offerte dès {{ mad($freeShippingThreshold) }}
        </div>
      </nav>
    </aside>
  </div>
</header>
