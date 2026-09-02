@extends('layouts.app')

@section('title', 'Boutique — SkinChemists Maroc')
@section('description', 'Tous les soins skinChemists disponibles au Maroc : sérums, crèmes, contour des yeux. Paiement à la livraison.')

@section('content')
  @php
    $activeFilters = array_filter([
        'categorie' => request('categorie'),
        'marque' => request('marque'),
        'gamme' => request('gamme'),
        'actif' => request('actif'),
        'besoin' => request('besoin'),
        'q' => request('q'),
    ]);
  @endphp

  <section class="sc-wrap" style="max-width:1320px;margin:0 auto;padding:44px 40px 0">
    <h1 class="sc-h1" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:38px;margin:0 0 10px">
      {{ request('marque')
          ?: (request('gamme')
          ?: (request('actif')
          ?: (request('besoin')
          ?: (request('q') ? 'Recherche : '.request('q') : 'Tous les soins')))) }}
    </h1>
    <p style="margin:0 0 30px;color:#6B6B6B;font-size:13.5px">{{ $products->total() }} produit{{ $products->total() > 1 ? 's' : '' }}</p>
  </section>

  <section class="sc-wrap sc-shop" style="max-width:1320px;margin:0 auto;padding:0 40px 74px;display:grid;grid-template-columns:230px 1fr;gap:46px;align-items:start">
    {{-- Filters: plain links, so every filtered view has its own shareable URL.
         On mobile they collapse behind a toggle, opened already when a filter
         is active so it is obvious why the list is short. --}}
    <aside x-data="{ open: {{ $activeFilters !== [] ? 'true' : 'false' }} }">
      <button type="button" class="sc-filter-toggle" @click="open = !open"
              style="width:100%;align-items:center;justify-content:space-between;background:none;border:1px solid #14120F;padding:13px 16px;margin-bottom:18px;cursor:pointer;color:#14120F;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">
        <span>Filtrer{{ $activeFilters !== [] ? ' ('.count($activeFilters).')' : '' }}</span>
        <span x-text="open ? '−' : '+'"></span>
      </button>

      <div class="sc-filters" :class="open ? 'sc-filters-open' : ''">
      @if ($activeFilters !== [])
        <a href="{{ route('shop') }}" style="display:inline-block;margin-bottom:24px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase;color:#14120F;border-bottom:1px solid #14120F;padding-bottom:3px">Effacer les filtres</a>
      @endif

      {{-- Several brands are distributed here, so the filter only appears
           once there is actually more than one to choose between. --}}
      @if ($brands->count() > 1)
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:14px">Marque</div>
        <div style="display:grid;gap:9px;margin-bottom:30px">
          @foreach ($brands as $brand)
            @php($isActive = request('marque') === $brand)
            <a href="{{ route('shop', array_merge($activeFilters, ['marque' => $isActive ? null : $brand])) }}"
               style="font-size:13.5px;color:{{ $isActive ? '#14120F' : '#6B6B6B' }};{{ $isActive ? 'font-weight:500' : '' }}">{{ $brand }}</a>
          @endforeach
        </div>
      @endif

      @if ($gammes->isNotEmpty())
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:14px">Gamme</div>
        <div style="display:grid;gap:9px;margin-bottom:30px">
          @foreach ($gammes as $gamme)
            @php($isActive = request('gamme') === $gamme)
            <a href="{{ route('shop', array_merge($activeFilters, ['gamme' => $isActive ? null : $gamme])) }}"
               style="font-size:13.5px;color:{{ $isActive ? '#14120F' : '#6B6B6B' }};{{ $isActive ? 'font-weight:500' : '' }}">{{ $gamme }}</a>
          @endforeach
        </div>
      @endif

      <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:14px">Catégorie</div>
      <div style="display:grid;gap:9px;margin-bottom:30px">
        @foreach ($categories as $category)
          @php($isActive = request('categorie') === $category->slug)
          <a href="{{ route('shop', array_merge($activeFilters, ['categorie' => $isActive ? null : $category->slug])) }}"
             style="font-size:13.5px;color:{{ $isActive ? '#14120F' : '#6B6B6B' }};{{ $isActive ? 'font-weight:500' : '' }}">{{ $category->name }}</a>
        @endforeach
      </div>

      <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:14px">Actif</div>
      <div style="display:grid;gap:9px;margin-bottom:30px">
        @foreach ($ingredients as $ingredient)
          @php($isActive = request('actif') === $ingredient)
          <a href="{{ route('shop', array_merge($activeFilters, ['actif' => $isActive ? null : $ingredient])) }}"
             style="font-size:13.5px;color:{{ $isActive ? '#14120F' : '#6B6B6B' }};{{ $isActive ? 'font-weight:500' : '' }}">{{ $ingredient }}</a>
        @endforeach
      </div>

      <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:14px">Préoccupation</div>
      <div style="display:grid;gap:9px">
        @foreach ($concerns as $concern)
          @php($isActive = request('besoin') === $concern)
          <a href="{{ route('shop', array_merge($activeFilters, ['besoin' => $isActive ? null : $concern])) }}"
             style="font-size:13.5px;color:{{ $isActive ? '#14120F' : '#6B6B6B' }};{{ $isActive ? 'font-weight:500' : '' }}">{{ $concern }}</a>
        @endforeach
      </div>
      </div>
    </aside>

    <div>
      <form method="GET" action="{{ route('shop') }}" style="display:flex;justify-content:flex-end;margin-bottom:22px">
        @foreach ($activeFilters as $key => $value)
          <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
        @endforeach
        <select name="tri" onchange="this.form.submit()" style="border:1px solid #E6E6E6;padding:10px 14px;font-size:12px;color:#14120F;background:#FFFFFF">
          <option value="">Trier : populaires</option>
          <option value="prix-asc" @selected(request('tri') === 'prix-asc')>Prix croissant</option>
          <option value="prix-desc" @selected(request('tri') === 'prix-desc')>Prix décroissant</option>
          <option value="nouveautes" @selected(request('tri') === 'nouveautes')>Nouveautés</option>
        </select>
      </form>

      @if ($products->isEmpty())
        <p style="padding:60px 0;text-align:center;color:#9B9B9B">Aucun produit ne correspond à ces filtres.</p>
      @else
        <div class="sc-products" style="display:grid;grid-template-columns:repeat(3, 1fr);gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
          @foreach ($products as $product)
            @include('partials.product-card', ['product' => $product])
          @endforeach
        </div>

        <div style="margin-top:34px">{{ $products->links('partials.pagination') }}</div>
      @endif
    </div>
  </section>
@endsection
