<footer style="background:#14120F;color:#FFFFFF;margin-top:0">
  <div style="max-width:1320px;margin:0 auto;padding:70px 40px 34px;display:grid;grid-template-columns:1.4fr 1fr 1fr 1fr;gap:50px">
    <div>
      <img src="{{ asset('uploads/SKINCHEMIST-LOGO-WHITE.webp') }}" alt="skinChemists" style="height:38px;width:auto;margin-bottom:20px" />
      <p style="margin:0 0 20px;max-width:280px;opacity:0.62;font-size:13.5px">Distributeur agréé skinChemists au Maroc. Produits authentiques, importés et stockés à Casablanca.</p>
    </div>
    <div>
      <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;opacity:0.5;margin-bottom:16px">Boutique</div>
      <div style="display:grid;gap:10px;font-size:13.5px">
        <a href="{{ route('shop') }}" style="color:#FFFFFF;opacity:0.8">Tous les soins</a>
        <a href="{{ route('shop', ['tri' => 'populaires']) }}" style="color:#FFFFFF;opacity:0.8">Best-sellers</a>
        <a href="{{ route('bundles') }}" style="color:#FFFFFF;opacity:0.8">Coffrets &amp; rituels</a>
        <a href="{{ route('shop', ['actif' => 'Caviar']) }}" style="color:#FFFFFF;opacity:0.8">Édition limitée Caviar</a>
      </div>
    </div>
    <div>
      <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;opacity:0.5;margin-bottom:16px">Aide</div>
      <div style="display:grid;gap:10px;font-size:13.5px">
        <span style="opacity:0.8">Livraison &amp; délais</span>
        <span style="opacity:0.8">Paiement à la livraison</span>
        <span style="opacity:0.8">Retours</span>
        <a href="{{ route('tracking') }}" style="color:#FFFFFF;opacity:0.8">Suivre ma commande</a>
      </div>
    </div>
    <div>
      <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;opacity:0.5;margin-bottom:16px">Maison</div>
      <div style="display:grid;gap:10px;font-size:13.5px">
        <a href="{{ route('lab') }}" style="color:#FFFFFF;opacity:0.8">Le Lab</a>
        <span style="opacity:0.8">Authenticité</span>
        <span style="opacity:0.8">Mentions légales</span>
        <span style="opacity:0.8">Confidentialité</span>
      </div>
    </div>
  </div>
  <div style="max-width:1320px;margin:0 auto;padding:20px 40px 40px;border-top:1px solid rgba(255,255,255,0.14);display:flex;justify-content:space-between;font-size:11.5px;opacity:0.55">
    <span>© {{ date('Y') }} skinChemists Maroc</span>
    <span>Maroc · Français · MAD (د.م.)</span>
  </div>
</footer>
