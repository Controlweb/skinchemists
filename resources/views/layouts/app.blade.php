<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
@include('partials.seo')
<link rel="icon" href="{{ asset('uploads/favicon.png') }}" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,200;0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet" />
<style>
  *, *::before, *::after { box-sizing: border-box; }
  /* Until Alpine boots, x-show elements are still in the document. Without
     this they flash open on every page load — the mega menu, the search
     overlay, and every product gallery image at once. */
  [x-cloak] { display: none !important; }
  body { margin: 0; background: #FFFFFF; -webkit-font-smoothing: antialiased; }
  a { color: oklch(0.48 0.09 250); text-decoration: none; }
  a:hover { color: #14120F; }
  input, select, textarea, button { font-family: inherit; }
  ::selection { background: #14120F; color: #FFFFFF; }
  .scm-scroll::-webkit-scrollbar { height: 3px; }
  .scm-scroll::-webkit-scrollbar-thumb { background: #CFC7BA; }
  @keyframes scmIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
  @keyframes scmDrawer { from { transform: translateX(100%); } to { transform: none; } }
  @keyframes scmHeroA { from { opacity: 0; transform: translateX(56px) scale(0.965); } to { opacity: 1; transform: none; } }
  @keyframes scmHeroB { from { opacity: 0; transform: translateX(56px) scale(0.965); } to { opacity: 1; transform: none; } }
  @keyframes scmLineA { 0% { opacity: 0; transform: translateY(16px); } 16%, 74% { opacity: 1; transform: none; } 100% { opacity: 0; transform: translateY(-12px); } }
  @keyframes scmLineB { 0% { opacity: 0; transform: translateY(16px); } 16%, 74% { opacity: 1; transform: none; } 100% { opacity: 0; transform: translateY(-12px); } }
  @keyframes scmRise { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
  @keyframes scmRiseA { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
  @keyframes scmRiseB { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: none; } }
  @keyframes scmToast { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }

  /* ------------------------------------------------------------------ *
   * Responsive layer.
   *
   * The design carries its styling in inline style attributes, which beat
   * any stylesheet rule on specificity. Overriding them from a media query
   * therefore needs !important — that is a property of the markup we
   * inherited, not a shortcut. Rules are keyed off a small set of classes
   * added to the containers that actually need to reflow.
   * ------------------------------------------------------------------ */

  .sc-mobile-only { display: none !important; }

  /* Nav labels are two words ("Best-sellers", "Le Lab") and were breaking
     across lines once the bar got tight, which pushed the header taller. */
  .sc-header-bar nav a,
  .sc-header-bar nav button { white-space: nowrap; }

  /* Five links, a centred logo and the actions stop fitting comfortably
     before the mobile breakpoint. */
  @media (min-width: 861px) and (max-width: 1140px) {
    .sc-header-bar nav { gap: 15px !important; }
    .sc-header-bar nav a,
    .sc-header-bar nav button { font-size: 10px !important; letter-spacing: 0.1em !important; }
    .sc-header-bar { padding-left: 22px !important; padding-right: 22px !important; }
  }

  /* For elements whose visibility Alpine controls (x-show writes an inline
     display:none). A `display:block !important` breakpoint rule would beat
     that inline style and force the panel permanently open, so these are only
     ever *hidden* by CSS — never forced visible. */
  @media (min-width: 861px) {
    .sc-mobile-panel { display: none !important; }
  }

  @media (max-width: 1024px) {
    .sc-wrap { padding-left: 24px !important; padding-right: 24px !important; }
    .sc-grid-4 { grid-template-columns: repeat(2, 1fr) !important; }
    .sc-grid-3 { grid-template-columns: repeat(2, 1fr) !important; }
    .sc-shop { grid-template-columns: 1fr !important; gap: 28px !important; }
  }

  @media (max-width: 860px) {
    .sc-wrap { padding-left: 18px !important; padding-right: 18px !important; }

    /* Every two-column split becomes one column, in source order. */
    .sc-stack { grid-template-columns: 1fr !important; gap: 30px !important; }
    .sc-stack-tight { grid-template-columns: 1fr !important; gap: 16px !important; }

    /* The hero slides in from translateX(56px). On a desktop that overflow is
       absorbed by the page margins; on a phone it pushes the document 56px
       wider every 6 seconds, so the page jitters sideways on each rotation.
       Same animation names, redefined to rise vertically instead. */
    @keyframes scmHeroA { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    @keyframes scmHeroB { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }

    /* The one .sc-stack that does not follow source order. Stacked, the copy
       ran kicker -> title -> body -> two CTAs -> slider controls with the
       product shot last, well past the fold. The shot belongs between the
       title and the body, but those are children of .sc-hero-copy while the
       shot is its sibling, so order alone cannot interleave them:
       display:contents drops the copy wrapper out of the box tree and lifts
       its children into the hero grid, where all six can be ordered together.
       Vertical padding moves to .sc-hero for the same reason - a
       display:contents box paints nothing of its own. */
    .sc-hero { min-height: 0 !important; gap: 8px !important; padding-top: 26px !important; padding-bottom: 8px !important; }
    .sc-hero-title { font-size: 30px !important; }

    .sc-hero-copy { display: contents !important; }
    .sc-hero-copy > :nth-child(1) { order: 1; }   /* kicker */
    .sc-hero-copy > :nth-child(2) { order: 2; }   /* title */
    .sc-hero-media { order: 3; height: min(290px, 32vh) !important; padding: 4px 0 14px !important; }
    .sc-hero-copy > :nth-child(n+3) { order: 4; } /* body, CTAs, slider controls */

    .sc-h1 { font-size: 30px !important; }
    .sc-h2 { font-size: 24px !important; }
    .sc-section { padding-top: 44px !important; padding-bottom: 0 !important; }

    .sc-desktop-only { display: none !important; }
    .sc-mobile-only { display: block !important; }

    /* A 90px bar eats a third of a phone's viewport before any content. */
    .sc-header-bar { height: 64px !important; gap: 12px !important; }
    .sc-logo { height: 30px !important; }

    /* A sticky sidebar that is now above the content would pin the summary
       over the form it belongs to. */
    .sc-unstick { position: static !important; top: auto !important; }

    .sc-cta-full { width: 100% !important; text-align: center !important; }

    /* Right-aligned columns read as ragged text once they are full width. */
    .sc-align-left { justify-items: start !important; text-align: left !important; }

    .sc-lab-copy { padding: 0 20px 32px !important; }

    /* The coffret card's desktop padding left the contents list ~100px for a
       product name, wrapping every one onto three lines. */
    .sc-bundle-body { padding: 24px 18px !important; }
    .sc-bundle-media { padding: 28px 18px !important; min-height: 200px !important; }

    /* Price drops beneath the name so the name gets the full row width
       instead of wrapping a long product title onto three lines. */
    .sc-bundle-row { grid-template-columns: 42px 1fr !important; gap: 4px 12px !important; align-items: start !important; }
    .sc-bundle-row > :nth-child(3) { grid-column: 2 !important; }
    .sc-track-form { grid-template-columns: 1fr !important; }
    .sc-cited { grid-template-columns: 56px 1fr !important; gap: 12px !important; }
    .sc-cited > :nth-child(3), .sc-cited > :nth-child(4) { grid-column: 2 !important; }

    .sc-footer { grid-template-columns: 1fr 1fr !important; gap: 30px !important; }
    .sc-footer-legal { flex-direction: column !important; gap: 6px !important; }

    /* Stacked, the filter list would push every product below the fold, so it
       collapses behind a toggle and the catalogue stays the first thing seen. */
    .sc-filters { display: none !important; }
    .sc-filters.sc-filters-open { display: block !important; }
    .sc-filter-toggle { display: flex !important; }
  }

  .sc-filter-toggle { display: none; }

  @media (max-width: 560px) {
    .sc-footer { grid-template-columns: 1fr !important; gap: 24px !important; }
  }

  @media (max-width: 560px) {
    .sc-grid-4, .sc-grid-3, .sc-grid-2 { grid-template-columns: 1fr !important; }
    .sc-hero-title { font-size: 26px !important; }
    .sc-h1 { font-size: 26px !important; }

    /* Two per row keeps the catalogue scannable without a card per screen. */
    .sc-products { grid-template-columns: repeat(2, 1fr) !important; }
    .sc-products .sc-card-name { min-height: 34px !important; font-size: 13px !important; }
    .sc-products .sc-card-pad { padding: 12px 12px 16px !important; }

    /* At two cards per row a price must never break between the number and
       its currency — "849" on one line and "MAD" on the next reads as broken. */
    .sc-products .sc-card-price { font-size: 15px !important; gap: 3px 8px !important; }
    .sc-products .sc-card-price > span { white-space: nowrap !important; }

    /* Let the call to action sit on one line instead of stacking two words. */
    .sc-products button { padding: 12px 8px !important; font-size: 9.5px !important; letter-spacing: 0.1em !important; }
  }

  @media (max-width: 380px) {
    /* One card per row below this: two would leave ~140px of usable width. */
    .sc-products { grid-template-columns: 1fr !important; }
  }
</style>
@livewireStyles
</head>
<body>
<div style="min-height:100vh;background:#FFFFFF;color:#14120F;font-family:'Montserrat',system-ui,sans-serif;font-size:15px;line-height:1.6">
  @include('partials.header')

  <main>
    @if (session('status'))
      <div style="background:#EDF1F6;color:oklch(0.42 0.09 250);text-align:center;padding:12px 20px;font-size:13px">{{ session('status') }}</div>
    @endif
    @yield('content')
  </main>

  @include('partials.footer')
  @livewire('cart-drawer')
</div>
@livewireScripts
</body>
</html>
