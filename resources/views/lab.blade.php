@extends('layouts.app')

@section('title', 'Le Lab — SkinChemists Maroc')
@section('description', "Ce que font les actifs, dans quel ordre les appliquer, et comment adapter sa routine au climat marocain.")

@section('content')
  @php
    $placeholder = 'background:repeating-linear-gradient(135deg,#F4F4F4 0 8px,#EDEDED 8px 16px);display:flex;align-items:center;justify-content:center';
  @endphp

  <main style="max-width:1320px;margin:0 auto;padding:36px 40px 90px">
    <div style="font-size:11.5px;color:#6B6B6B;margin-bottom:26px">
      <a href="{{ route('home') }}" style="color:#6B6B6B">Accueil</a> / <span style="color:#14120F">Le Lab</span>
    </div>

    <div style="border-bottom:1px solid #E6E6E6;padding-bottom:28px;margin-bottom:36px">
      <h1 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:46px;margin:0 0 10px">Le Lab</h1>
      <p style="margin:0;color:#6B6B6B;font-size:15px;max-width:600px">Ce que font les actifs, dans quel ordre les appliquer, et comment adapter sa routine au climat marocain.</p>
    </div>

    @if ($featured)
      <article style="display:grid;grid-template-columns:1.25fr 1fr;gap:48px;align-items:center;border:1px solid #E6E6E6;margin-bottom:36px">
        <a href="{{ route('article', $featured) }}" style="aspect-ratio:16/10;{{ $placeholder }}">
          @if ($featured->image_path)
            <span style="width:100%;height:100%;background-image:url('{{ image_url($featured->image_path) }}');background-repeat:no-repeat;background-position:center;background-size:cover"></span>
          @else
            <span style="font-family:ui-monospace,Menlo,monospace;font-size:11px;letter-spacing:0.08em;color:#9B9B9B">[ visuel éditorial ]</span>
          @endif
        </a>

        <div style="padding:40px 44px 40px 0;display:grid;gap:16px">
          <span style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B">{{ $featured->category }} · À la une</span>
          <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:34px;line-height:1.15;margin:0">
            <a href="{{ route('article', $featured) }}" style="color:#14120F">{{ $featured->title }}</a>
          </h2>
          <p style="margin:0;color:#6B6B6B;font-size:14.5px;line-height:1.6">{{ $featured->excerpt }}</p>
          <div style="display:flex;align-items:center;gap:14px;font-size:12px;color:#9B9B9B">
            <span>{{ $featured->author }}</span><span>·</span>
            <span>{{ $featured->published_at->translatedFormat('j F Y') }}</span><span>·</span>
            <span>{{ $featured->read_minutes }} min</span>
          </div>
          <a href="{{ route('article', $featured) }}"
             style="justify-self:start;background:#14120F;color:#FFFFFF;padding:14px 26px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase">Lire l'article</a>
        </div>
      </article>
    @endif

    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:28px">
      @foreach ($articles->where('id', '!=', $featured?->id) as $article)
        <article style="display:flex;flex-direction:column;gap:14px">
          <a href="{{ route('article', $article) }}" style="aspect-ratio:4/3;{{ $placeholder }}">
            @if ($article->image_path)
              <span style="width:100%;height:100%;background-image:url('{{ image_url($article->image_path) }}');background-repeat:no-repeat;background-position:center;background-size:cover"></span>
            @else
              <span style="font-family:ui-monospace,Menlo,monospace;font-size:10.5px;letter-spacing:0.08em;color:#9B9B9B">[ visuel éditorial ]</span>
            @endif
          </a>
          <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B">{{ $article->category }} · {{ $article->read_minutes }} min</div>
          <a href="{{ route('article', $article) }}"
             style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:22px;line-height:1.25;color:#14120F">{{ $article->title }}</a>
          <p style="margin:0;font-size:13.5px;color:#6B6B6B;line-height:1.55">{{ $article->excerpt }}</p>
        </article>
      @endforeach
    </div>

    @if ($articles->isEmpty())
      <p style="color:#9B9B9B;padding:40px 0">Aucun article publié pour le moment.</p>
    @endif
  </main>
@endsection
