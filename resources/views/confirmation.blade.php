@extends('layouts.app')

@section('title', 'Commande '.$order->number.' confirmée')

@section('content')
  <section class="sc-wrap" style="max-width:820px;margin:0 auto;padding:64px 40px 74px">
    <div style="font-size:10.5px;letter-spacing:0.22em;text-transform:uppercase;color:#3F6B45;margin-bottom:18px">Commande confirmée</div>
    <h1 class="sc-h1" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:34px;margin:0 0 16px">Merci {{ $order->first_name }}.</h1>
    <p style="margin:0 0 34px;color:#454545;font-size:15.5px;max-width:520px">
      Votre commande <strong>{{ $order->number }}</strong> est enregistrée. Nous vous appellerons au
      {{ $order->phone }} pour la confirmer avant expédition. Vous réglerez {{ mad($order->total_cents) }} à la livraison.
    </p>

    <div style="border:1px solid #E6E6E6">
      <div style="padding:20px 24px;border-bottom:1px solid #E6E6E6;display:flex;justify-content:space-between;font-size:12px;letter-spacing:0.14em;text-transform:uppercase;color:#9B9B9B">
        <span>Récapitulatif</span><span>{{ $order->created_at->format('d/m/Y') }}</span>
      </div>

      @foreach ($order->items as $item)
        <div style="display:flex;gap:16px;padding:18px 24px;border-bottom:1px solid #F4F4F4;align-items:center">
          @if ($item->image_path)
            <span style="width:56px;height:56px;flex:none;background:#FAFAFA;background-image:url('{{ image_url($item->image_path) }}');background-repeat:no-repeat;background-position:center;background-size:contain"></span>
          @endif
          <span style="flex:1;font-size:13.5px;line-height:1.4">{{ $item->name }}<br><span style="color:#9B9B9B;font-size:12px">{{ $item->sku }} · × {{ $item->quantity }}</span></span>
          <span style="font-size:13.5px">{{ mad($item->line_total_cents) }}</span>
        </div>
      @endforeach

      <div style="padding:20px 24px">
        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:9px">
          <span style="color:#6B6B6B">Sous-total</span><span>{{ mad($order->subtotal_cents) }}</span>
        </div>
        @if ($order->discount_cents > 0)
          <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:9px;color:#3F6B45">
            <span>Remise {{ $order->coupon_code }}</span><span>−{{ mad($order->discount_cents) }}</span>
          </div>
        @endif
        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:14px">
          <span style="color:#6B6B6B">Livraison ({{ $order->shipping_method === 'express' ? 'express' : 'standard' }})</span>
          <span>{{ $order->shipping_cents === 0 ? 'Offerte' : mad($order->shipping_cents) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:17px;padding-top:14px;border-top:1px solid #E6E6E6">
          <span>Total à payer</span><span>{{ mad($order->total_cents) }}</span>
        </div>
      </div>
    </div>

    <div style="margin-top:26px;font-size:13.5px;color:#6B6B6B;line-height:1.7">
      <div>{{ $order->customerName() }}</div>
      <div>{{ $order->address }}</div>
      <div>{{ $order->city }}@if ($order->zip) · {{ $order->zip }}@endif</div>
    </div>

    <a href="{{ route('shop') }}" style="display:inline-block;margin-top:34px;background:#14120F;color:#FFFFFF;padding:15px 28px;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">Continuer mes achats</a>
  </section>
@endsection
