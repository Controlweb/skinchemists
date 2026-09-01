@extends('layouts.app')

@section('title', 'Panier — SkinChemists Maroc')

@section('content')
  <section style="max-width:1000px;margin:0 auto;padding:52px 40px 74px">
    <h1 style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:34px;margin:0 0 30px">Votre panier</h1>

    @if ($lines->isEmpty())
      <p style="color:#6B6B6B;font-size:14.5px">Votre panier est vide.</p>
      <a href="{{ route('shop') }}" style="display:inline-block;margin-top:20px;background:#14120F;color:#FFFFFF;padding:15px 28px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">Voir les soins</a>
    @else
      <div style="display:grid;grid-template-columns:1fr 320px;gap:50px;align-items:start">
        <div style="border-top:1px solid #E6E6E6">
          @foreach ($lines as $line)
            @php($product = $line['product'])
            <div style="display:flex;gap:20px;padding:22px 0;border-bottom:1px solid #E6E6E6">
              <a href="{{ route('product', $product) }}" style="width:96px;height:96px;flex:none;background:#FAFAFA;background-image:url('{{ asset($product->primaryImage()) }}');background-repeat:no-repeat;background-position:center;background-size:contain"></a>
              <div style="flex:1">
                <a href="{{ route('product', $product) }}" style="display:block;font-size:14.5px;color:#14120F;margin-bottom:6px">{{ $product->name }}</a>
                <div style="font-size:13px;color:#6B6B6B">{{ mad($product->effectivePriceCents()) }} × {{ $line['quantity'] }}</div>
              </div>
              <div style="font-size:14.5px">{{ mad($product->effectivePriceCents() * $line['quantity']) }}</div>
            </div>
          @endforeach
        </div>

        <aside style="border:1px solid #E6E6E6;padding:26px">
          <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:10px">
            <span style="color:#6B6B6B">Sous-total</span><span>{{ mad($pricing->subtotal) }}</span>
          </div>
          @if ($pricing->discount > 0)
            <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:10px;color:#3F6B45">
              <span>Remise coffret</span><span>−{{ mad($pricing->discount) }}</span>
            </div>
          @endif
          <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:16px">
            <span style="color:#6B6B6B">Livraison estimée</span><span>{{ $pricing->shipping === 0 ? 'Offerte' : mad($pricing->shipping) }}</span>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:16px;padding-top:16px;border-top:1px solid #E6E6E6;margin-bottom:22px">
            <span>Total</span><span>{{ mad($pricing->total) }}</span>
          </div>
          <a href="{{ route('checkout') }}" style="display:block;text-align:center;background:#14120F;color:#FFFFFF;padding:15px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">Passer la commande</a>
        </aside>
      </div>
    @endif
  </section>
@endsection
