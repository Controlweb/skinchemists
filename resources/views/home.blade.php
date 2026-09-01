@extends('layouts.app')

@section('title', 'SkinChemists Maroc — Soins scientifiques, livrés partout au Maroc')

@section('content')
  @php
    // Hero slides carry the prototype's copy. The image for each is looked up
    // by SKU so a catalogue edit cannot leave the hero pointing at nothing.
    $heroSlides = collect([
        ['sku' => 'SC-259', 'kicker' => 'Laboratoire britannique · Maroc',
         'lines' => ['LA COSMÉTIQUE', 'FORMULÉE COMME', 'UNE ORDONNANCE.'],
         'body' => 'Sérums et crèmes à concentrations actives élevées, désormais disponibles au Maroc avec paiement à la livraison.',
         'ctaLabel' => 'Acheter les soins', 'ctaUrl' => route('shop')],
        ['sku' => 'SC-155', 'kicker' => 'Édition limitée · Caviar',
         'lines' => ['LE PROTOCOLE', 'JOUR ET NUIT', 'LE PLUS CONCENTRÉ.'],
         'body' => 'Extrait de caviar, fleur de Tiaré et huiles végétales. Deux gestes, une peau visiblement raffermie.',
         'ctaLabel' => 'Voir la gamme Caviar', 'ctaUrl' => route('shop', ['actif' => 'Caviar'])],
        ['sku' => 'SC-215', 'kicker' => 'Écran solaire · SPF 50',
         'lines' => ['LE SOLEIL MAROCAIN', 'NE PREND PAS', 'DE VACANCES.'],
         'body' => "Écran SPF 50 à 1% d'acide hyaluronique : protection quotidienne et hydratation en un seul geste, toute l'année.",
         'ctaLabel' => "Voir l'écran SPF 50", 'ctaUrl' => route('shop', ['q' => 'SPF 50'])],
    ])->map(function ($slide) {
        $product = \App\Models\Product::with('images')->where('sku', $slide['sku'])->first();
        $slide['img'] = $product?->primaryImageUrl();
        return $slide;
    })->filter(fn ($slide) => filled($slide['img']))->values();
  @endphp

  {{-- Hero --}}
  @if ($heroSlides->isNotEmpty())
  <section style="border-bottom:1px solid #E6E6E6;background:#FFFFFF"
           x-data="{ i: 0, n: {{ $heroSlides->count() }}, timer: null,
                     start() { this.timer = setInterval(() => this.i = (this.i + 1) % this.n, 6300) },
                     stop() { clearInterval(this.timer) },
                     go(k) { this.i = k; this.stop(); this.start() } }"
           x-init="start()" @mouseenter="stop()" @mouseleave="start()">
    <div style="max-width:1320px;margin:0 auto;padding:0 40px;display:grid;grid-template-columns:1fr 1fr;align-items:center;gap:60px;min-height:640px">
      @foreach ($heroSlides as $index => $slide)
        <template x-if="i === {{ $index }}">
          <div style="padding:80px 0">
            <div style="font-size:10.5px;letter-spacing:0.22em;text-transform:uppercase;color:#6B6B6B;margin-bottom:22px;animation:scmHeroA 0.5s cubic-bezier(0.22,0.61,0.36,1) both">{{ $slide['kicker'] }}</div>
            <h1 style="font-family:'Montserrat',sans-serif;font-weight:300;font-size:44px;line-height:1.14;margin:0 0 22px;letter-spacing:0.01em">
              @foreach ($slide['lines'] as $line)
                <span style="display:block;animation:scmRise 0.6s cubic-bezier(0.22,0.61,0.36,1) {{ $loop->index * 0.12 }}s both">{{ $line }}</span>
              @endforeach
            </h1>
            <p style="margin:0 0 34px;max-width:420px;color:#454545;font-size:16px;animation:scmHeroA 0.55s cubic-bezier(0.22,0.61,0.36,1) 0.1s both">{{ $slide['body'] }}</p>
            <div style="display:flex;gap:14px;animation:scmHeroA 0.55s cubic-bezier(0.22,0.61,0.36,1) 0.18s both">
              <a href="{{ $slide['ctaUrl'] }}" style="background:#14120F;color:#FFFFFF;border:0;padding:16px 30px;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">{{ $slide['ctaLabel'] }}</a>
              <a href="{{ route('shop') }}" style="background:none;color:#14120F;border:1px solid #14120F;padding:16px 30px;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">Trouver ma routine</a>
            </div>

            <div style="display:flex;align-items:center;gap:20px;margin-top:52px">
              <div style="display:flex;gap:9px">
                @foreach ($heroSlides as $dot => $unused)
                  <button type="button" @click="go({{ $dot }})" aria-label="Slide {{ $dot + 1 }}"
                          :style="`width:${i === {{ $dot }} ? '34px' : '16px'};height:3px;border:0;padding:0;cursor:pointer;background:${i === {{ $dot }} ? '#14120F' : '#CFC7BA'};transition:width 0.3s ease, background 0.3s ease`"></button>
                @endforeach
              </div>
              <div style="display:flex;gap:1px;margin-left:auto">
                <button type="button" @click="go((i - 1 + n) % n)" aria-label="Précédent" style="width:40px;height:40px;border:1px solid #E6E6E6;background:#FFFFFF;cursor:pointer;color:#14120F;font-size:15px;line-height:1">←</button>
                <button type="button" @click="go((i + 1) % n)" aria-label="Suivant" style="width:40px;height:40px;border:1px solid #E6E6E6;background:#FFFFFF;cursor:pointer;color:#14120F;font-size:15px;line-height:1">→</button>
              </div>
            </div>
          </div>
        </template>
      @endforeach

      <div style="align-self:stretch;display:flex;align-items:center;justify-content:center;background:#FFFFFF;padding:24px 0;overflow:hidden">
        @foreach ($heroSlides as $index => $slide)
          <template x-if="i === {{ $index }}">
            <div role="img" aria-label="{{ $slide['kicker'] }}"
                 style="width:100%;height:560px;background-image:url('{{ $slide['img'] }}');background-repeat:no-repeat;background-position:center;background-size:contain;mix-blend-mode:multiply;animation:scmHeroB 0.85s cubic-bezier(0.22,0.61,0.36,1) both"></div>
          </template>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  {{-- Best-sellers --}}
  <section style="max-width:1320px;margin:0 auto;padding:74px 40px 0">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px">
      <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:34px;margin:0">Best-sellers au Maroc</h2>
      <a href="{{ route('shop') }}" style="border-bottom:1px solid #14120F;padding:0 0 3px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;color:#14120F">Tout voir</a>
    </div>
    <div class="scm-scroll" style="display:grid;grid-auto-flow:column;grid-auto-columns:264px;gap:1px;overflow-x:auto;padding-bottom:14px;background:#E6E6E6">
      @foreach ($bestSellers as $product)
        @include('partials.product-card', ['product' => $product])
      @endforeach
    </div>
  </section>

  {{-- Shop by active ingredient --}}
  <section style="max-width:1320px;margin:0 auto;padding:74px 40px 0">
    <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:34px;margin:0 0 28px">Acheter par actif</h2>
    <div style="display:grid;grid-template-columns:repeat(4, 1fr);gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
      @foreach ($navIngredients as $ingredient => $ingredientSlug)
        <a href="{{ $ingredientSlug ? route('ingredient', $ingredientSlug) : route('shop', ['actif' => $ingredient]) }}"
           style="background:#FFFFFF;padding:34px 26px;color:#14120F;display:flex;align-items:center;justify-content:space-between;font-size:15px">
          <span>{{ $ingredient }}</span>
          <span style="color:#9B9B9B">→</span>
        </a>
      @endforeach
    </div>
  </section>

  {{-- Campaign band --}}
  <section style="background:#14120F;color:#FFFFFF;margin-top:74px">
    <div style="max-width:1320px;margin:0 auto;padding:80px 40px;display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center">
      <div>
        <div style="font-size:10.5px;letter-spacing:0.22em;text-transform:uppercase;opacity:0.6;margin-bottom:20px">Le laboratoire</div>
        <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;font-size:38px;line-height:1.15;margin:0 0 20px">Des concentrations que l'on trouve d'ordinaire en cabinet.</h2>
        <p style="margin:0 0 30px;opacity:0.72;max-width:460px;font-size:15.5px">Chaque formule est développée au Royaume-Uni puis importée et stockée à Casablanca. Vous payez à la livraison, partout au Maroc.</p>
        <a href="{{ route('shop') }}" style="display:inline-block;background:#FFFFFF;color:#14120F;padding:16px 30px;font-size:11px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">Découvrir les soins</a>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1px;background:rgba(255,255,255,0.14)">
        @foreach ([['Authentique', 'Distributeur agréé'], ['Paiement', 'À la livraison'], ['Livraison', 'Offerte dès ' . mad($freeShippingThreshold ?? 60000)], ['Stock', 'Casablanca']] as [$label, $value])
          <div style="background:#14120F;padding:26px">
            <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;opacity:0.5;margin-bottom:8px">{{ $label }}</div>
            <div style="font-size:15px">{{ $value }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- Customer reviews --}}
  @if ($reviews->isNotEmpty())
  <section style="max-width:1320px;margin:0 auto;padding:74px 40px">
    <div style="display:flex;align-items:flex-end;justify-content:space-between;margin-bottom:28px">
      <h2 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:34px;margin:0">Avis clients</h2>
      <div style="font-size:13.5px;color:#6B6B6B"><span style="color:#14120F">★</span> {{ $ratingAvg }} / 5</div>
    </div>
    <div style="display:grid;grid-template-columns:repeat({{ min($reviews->count(), 4) }}, 1fr);gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
      @foreach ($reviews as $review)
        <figure style="background:#FFFFFF;padding:30px 26px;margin:0">
          <div style="color:#14120F;margin-bottom:12px;font-size:13px">{{ str_repeat('★', $review->rating) }}<span style="color:#CFC7BA">{{ str_repeat('★', 5 - $review->rating) }}</span></div>
          <blockquote style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#454545">{{ $review->body }}</blockquote>
          <figcaption style="font-size:11.5px;color:#9B9B9B">
            {{ $review->author }}@if ($review->verified) · <span style="color:#3F6B45">Achat vérifié</span>@endif
          </figcaption>
        </figure>
      @endforeach
    </div>
  </section>
  @endif
@endsection
