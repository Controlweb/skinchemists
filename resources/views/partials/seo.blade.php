{{--
  Every meta tag the site emits, in one place.

  Views keep declaring @section('title') / @section('description') as before;
  Seo::resolve layers the row override and the site defaults underneath, so a
  view that says nothing still gets a full set rather than an empty <title>.

  Type-specific Open Graph (article:*, product:*) comes from the view that owns
  the type, through @section('ogTags') — the layout has no business knowing what
  a product costs.
--}}
@php($seo = \App\Support\Seo::resolve([
    'title' => trim($__env->yieldContent('title')) ?: null,
    'description' => trim($__env->yieldContent('description')) ?: null,
    'image' => trim($__env->yieldContent('seoImage')) ?: null,
    'imageAlt' => trim($__env->yieldContent('seoImageAlt')) ?: null,
    'type' => trim($__env->yieldContent('seoType')) ?: 'website',
    'robots' => trim($__env->yieldContent('seoRobots')) ?: null,
]))

<title>{{ $seo['title'] }}</title>
@if ($seo['description'])
  <meta name="description" content="{{ $seo['description'] }}" />
@endif
<meta name="robots" content="{{ $seo['robots'] }}" />
<link rel="canonical" href="{{ $seo['canonical'] }}" />

@if ($seo['verification'])
  <meta name="google-site-verification" content="{{ $seo['verification'] }}" />
@endif

{{-- Open Graph: what Facebook, WhatsApp, LinkedIn and iMessage read. --}}
<meta property="og:type" content="{{ $seo['type'] }}" />
<meta property="og:site_name" content="{{ $seo['siteName'] }}" />
<meta property="og:title" content="{{ $seo['title'] }}" />
<meta property="og:url" content="{{ $seo['canonical'] }}" />
<meta property="og:locale" content="fr_MA" />
@if ($seo['description'])
  <meta property="og:description" content="{{ $seo['description'] }}" />
@endif
@if ($seo['image'])
  <meta property="og:image" content="{{ $seo['image'] }}" />
  @if (\Illuminate\Support\Str::startsWith($seo['image'], 'https://'))
    <meta property="og:image:secure_url" content="{{ $seo['image'] }}" />
  @endif
  {{-- Declared size: without it the first share of a link shows no preview
       while the crawler fetches the file, and only later shares get a card. --}}
  @if ($seo['imageSize'])
    <meta property="og:image:width" content="{{ $seo['imageSize']['width'] }}" />
    <meta property="og:image:height" content="{{ $seo['imageSize']['height'] }}" />
  @endif
  <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}" />
@endif
@yield('ogTags')

{{-- Twitter reads Open Graph as a fallback, but naming the card type is what
     decides between a thumbnail strip and a full-width image. --}}
<meta name="twitter:card" content="{{ $seo['twitterCard'] }}" />
@if ($seo['twitterSite'])
  <meta name="twitter:site" content="{{ $seo['twitterSite'] }}" />
@endif

<script type="application/ld+json">{!! json_encode(\App\Support\Seo::organization(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@hasSection('jsonLd')
  <script type="application/ld+json">@yield('jsonLd')</script>
@endif
