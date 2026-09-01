@extends('layouts.app')

@section('title', $article->title.' — Le Lab')
@section('description', Str::limit(strip_tags($article->excerpt ?? ''), 155))

@section('content')
  @php
    $jsonLd = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'description' => strip_tags($article->excerpt ?? ''),
        'author' => ['@type' => 'Person', 'name' => $article->author],
        'datePublished' => $article->published_at?->toAtomString(),
        'dateModified' => $article->updated_at?->toAtomString(),
        'mainEntityOfPage' => route('article', $article),
        'publisher' => ['@type' => 'Organization', 'name' => 'skinChemists Maroc'],
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  @endphp
  <script type="application/ld+json">{!! $jsonLd !!}</script>

  <main style="padding-bottom:90px">
    <div style="max-width:820px;margin:0 auto;padding:36px 40px 0">
      <div style="font-size:11.5px;color:#6B6B6B;margin-bottom:34px">
        <a href="{{ route('home') }}" style="color:#6B6B6B">Accueil</a> /
        <a href="{{ route('lab') }}" style="color:#6B6B6B">Le Lab</a> /
        <span style="color:#14120F">{{ $article->category }}</span>
      </div>

      <div style="font-size:10.5px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:18px">{{ $article->category }}</div>
      <h1 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.02em;font-size:44px;line-height:1.12;margin:0 0 20px;text-wrap:balance">{{ $article->title }}</h1>

      <div style="display:flex;align-items:center;gap:14px;font-size:12.5px;color:#6B6B6B;padding-bottom:30px;border-bottom:1px solid #E6E6E6">
        <span>{{ $article->author }}</span><span>·</span>
        <span>{{ $article->published_at->translatedFormat('j F Y') }}</span><span>·</span>
        <span>{{ $article->read_minutes }} min</span>
      </div>
    </div>

    <div style="max-width:1100px;margin:34px auto 0;padding:0 40px">
      <div style="aspect-ratio:21/9;background:repeating-linear-gradient(135deg,#F4F4F4 0 8px,#EDEDED 8px 16px);display:flex;align-items:center;justify-content:center">
        @if ($article->image_path)
          <span style="width:100%;height:100%;background-image:url('{{ image_url($article->image_path) }}');background-repeat:no-repeat;background-position:center;background-size:cover"></span>
        @else
          <span style="font-family:ui-monospace,Menlo,monospace;font-size:11px;letter-spacing:0.08em;color:#9B9B9B">[ visuel éditorial ]</span>
        @endif
      </div>
    </div>

    <div style="max-width:820px;margin:0 auto;padding:44px 40px 0">
      @if ($article->lead)
        <p style="margin:0 0 26px;font-size:19px;line-height:1.55;color:#14120F">{{ $article->lead }}</p>
      @endif

      @foreach ($article->body ?? [] as $section)
        <div style="margin-bottom:30px">
          <h2 style="font-family:'Montserrat',sans-serif;font-weight:400;font-size:21px;margin:0 0 12px">{{ $section['h'] ?? '' }}</h2>
          <p style="margin:0;font-size:16px;line-height:1.7;color:#454545">{{ $section['p'] ?? '' }}</p>
        </div>
      @endforeach

      @if ($article->products->isNotEmpty())
        <div style="border-top:1px solid #E6E6E6;padding-top:30px;margin-top:10px">
          <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:18px">Produits cités</div>
          <div style="display:grid;gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
            @foreach ($article->products as $product)
              <div style="background:#FFFFFF;display:grid;grid-template-columns:56px 1fr auto 150px;gap:16px;align-items:center;padding:14px 16px">
                <span style="width:56px;height:56px;background-color:#FAFAFA;background-image:url('{{ $product->primaryImageUrl() }}');background-repeat:no-repeat;background-position:center;background-size:contain"></span>
                <a href="{{ route('product', $product) }}" style="font-size:14px;color:#14120F">{{ $product->name }}</a>
                <span style="font-size:14px">{{ mad($product->effectivePriceCents()) }}</span>
                @livewire('add-to-cart', ['product' => $product], key('cited-'.$product->id))
              </div>
            @endforeach
          </div>
        </div>
      @endif

      <div style="margin-top:40px;display:flex;gap:12px">
        <a href="{{ route('lab') }}" style="border:1px solid #14120F;color:#14120F;padding:14px 26px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase">Tous les articles</a>
        @if ($next)
          <a href="{{ route('article', $next) }}" style="background:#14120F;color:#FFFFFF;padding:14px 26px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase">Article suivant</a>
        @endif
      </div>
    </div>
  </main>
@endsection
