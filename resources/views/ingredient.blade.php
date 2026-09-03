@extends('layouts.app')

@section('title', $ingredient->meta_title ?: $ingredient->name.' — Actif')
@section('description', $ingredient->meta_description ?: strip_tags($ingredient->intro ?? ''))

@section('content')
  <main style="padding-bottom:90px">
    <section style="border-bottom:1px solid #E6E6E6">
      <div class="sc-wrap" style="max-width:1320px;margin:0 auto;padding:36px 40px 60px">
        <div style="font-size:11.5px;color:#6B6B6B;margin-bottom:34px">
          <a href="{{ route('home') }}" style="color:#6B6B6B">Accueil</a> /
          <a href="{{ route('shop') }}" style="color:#6B6B6B">Actifs</a> /
          <span style="color:#14120F">{{ $ingredient->name }}</span>
        </div>

        <div class="sc-stack" style="display:grid;grid-template-columns:1.2fr 1fr;gap:70px;align-items:center">
          <div>
            <div style="font-size:10.5px;letter-spacing:0.22em;text-transform:uppercase;color:#9B9B9B;margin-bottom:20px">Actif · {{ $products->count() }} produit{{ $products->count() > 1 ? 's' : '' }}</div>
            <h1 class="sc-h1" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.02em;font-size:56px;line-height:1.05;margin:0 0 20px">{{ $ingredient->name }}</h1>
            <p style="margin:0 0 26px;font-size:17px;line-height:1.55;color:#454545;max-width:520px">{{ $ingredient->intro }}</p>
            @if ($products->isNotEmpty())
              <a href="{{ route('shop', ['actif' => $ingredient->name]) }}"
                 style="display:inline-block;background:#14120F;color:#FFFFFF;padding:16px 30px;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">Voir les {{ $products->count() }} produits</a>
            @endif
          </div>

          <div style="background:#FAFAFA;display:flex;align-items:center;justify-content:center;padding:40px;min-height:340px">
            @if ($products->first()?->primaryImage())
              <span style="width:100%;height:280px;background-image:url('{{ $products->first()->primaryImageUrl() }}');background-repeat:no-repeat;background-position:center;background-size:contain;mix-blend-mode:multiply"></span>
            @endif
          </div>
        </div>
      </div>
    </section>

    <section class="sc-wrap sc-grid-3" style="max-width:1320px;margin:0 auto;padding:60px 40px 0;display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
      <div style="background:#FFFFFF;padding:34px 30px">
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:16px">Ce que c'est</div>
        <p style="margin:0;font-size:14.5px;line-height:1.6;color:#454545">{{ $ingredient->what }}</p>
      </div>

      <div style="background:#FFFFFF;padding:34px 30px">
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:16px">Bienfaits</div>
        <ul style="margin:0;padding:0;list-style:none;display:grid;gap:10px">
          @foreach ($ingredient->benefits ?? [] as $benefit)
            <li style="display:grid;grid-template-columns:14px 1fr;gap:11px;font-size:14.5px;color:#454545">
              <span style="color:oklch(0.48 0.09 250)">—</span><span>{{ $benefit }}</span>
            </li>
          @endforeach
        </ul>
      </div>

      <div style="background:#FFFFFF;padding:34px 30px">
        <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:16px">Comment l'utiliser</div>
        <p style="margin:0 0 14px;font-size:14.5px;line-height:1.6;color:#454545">{{ $ingredient->how }}</p>
        <p style="margin:0;font-size:13px;color:#6B6B6B">Pour qui : {{ $ingredient->who }}</p>
      </div>
    </section>

    @if ($products->isNotEmpty())
      <section class="sc-wrap" style="max-width:1320px;margin:0 auto;padding:60px 40px 0">
        <h2 class="sc-h2" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:32px;margin:0 0 24px">Produits contenant {{ $ingredient->name }}</h2>
        <div class="sc-products sc-grid-4" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
          @foreach ($products as $product)
            @include('partials.product-card', ['product' => $product])
          @endforeach
        </div>
      </section>
    @endif
  </main>
@endsection
