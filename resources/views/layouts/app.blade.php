<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>@yield('title', 'SkinChemists Maroc — Soins scientifiques')</title>
<meta name="description" content="@yield('description', 'SkinChemists Maroc : soins anti-âge formulés au Caviar, Rétinol, Acide Hyaluronique et Vitamine C. Livraison partout au Maroc, paiement à la livraison.')" />
<link rel="canonical" href="{{ url()->current() }}" />
<link rel="icon" href="{{ asset('uploads/favicon.png') }}" />
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,200;0,300;0,400;0,500;0,600;1,300&display=swap" rel="stylesheet" />
<style>
  *, *::before, *::after { box-sizing: border-box; }
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
