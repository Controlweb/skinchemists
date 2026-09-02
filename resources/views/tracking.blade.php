@extends('layouts.app')

@section('title', 'Suivi de commande — SkinChemists Maroc')

@section('content')
  @php
    $field = 'width:100%;border:1px solid #E6E6E6;padding:13px 14px;font-size:14px;color:#14120F;outline:none;background:#FFFFFF';
    $label = 'display:block;font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#9B9B9B;margin-bottom:8px';
    $statusColors = [
        'nouvelle' => '#EDF1F6', 'confirmee' => '#EDF1F6', 'preparation' => '#F4EFE4',
        'expediee' => '#EAF0EA', 'livree' => '#E8EDE8', 'annulee' => '#F7E9E7',
    ];
  @endphp

  <main class="sc-wrap" style="max-width:820px;margin:0 auto;padding:52px 40px 90px">
    <div style="font-size:11.5px;color:#6B6B6B;margin-bottom:26px">
      <a href="{{ route('home') }}" style="color:#6B6B6B">Accueil</a> / <span style="color:#14120F">Suivi de commande</span>
    </div>

    <h1 class="sc-h1" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:38px;margin:0 0 12px">Suivre ma commande</h1>
    <p style="margin:0 0 32px;color:#6B6B6B;font-size:15px;max-width:520px">
      Entrez le numéro figurant sur votre confirmation et le téléphone utilisé lors de la commande.
    </p>

    @if ($errors->any())
      <div style="background:#F7E9E7;color:#A83A30;padding:16px 18px;margin-bottom:26px;font-size:13.5px">
        {{ $errors->first() }}
      </div>
    @endif

    <form method="POST" action="{{ route('tracking.find') }}" class="sc-track-form" style="display:grid;grid-template-columns:1fr 1fr auto;gap:16px;align-items:end;margin-bottom:44px">
      @csrf
      <div>
        <label style="{{ $label }}" for="number">Numéro de commande</label>
        <input id="number" name="number" value="{{ old('number') }}" placeholder="SCM-1043" required style="{{ $field }}" />
      </div>
      <div>
        <label style="{{ $label }}" for="phone">Téléphone</label>
        <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="06 61 22 84 10" required style="{{ $field }}" />
      </div>
      <button type="submit" style="background:#14120F;color:#FFFFFF;border:0;padding:14px 26px;cursor:pointer;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500;height:47px">Suivre</button>
    </form>

    @if ($order)
      <div style="border:1px solid #E6E6E6">
        <div style="padding:22px 24px;border-bottom:1px solid #E6E6E6;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap">
          <div>
            <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:6px">Commande</div>
            <div style="font-size:20px">{{ $order->number }}</div>
          </div>
          <span style="background:{{ $statusColors[$order->status] ?? '#F4F4F4' }};padding:8px 14px;font-size:11px;letter-spacing:0.14em;text-transform:uppercase">{{ $order->statusLabel() }}</span>
        </div>

        @if ($order->tracking)
          <div style="padding:16px 24px;border-bottom:1px solid #E6E6E6;font-size:13.5px;color:#6B6B6B">
            N° de suivi transporteur : <strong style="color:#14120F">{{ $order->tracking }}</strong>
          </div>
        @endif

        @foreach ($order->items as $item)
          <div style="display:flex;gap:16px;padding:18px 24px;border-bottom:1px solid #F4F4F4;align-items:center">
            @if ($item->image_path)
              <span style="width:56px;height:56px;flex:none;background:#FAFAFA;background-image:url('{{ image_url($item->image_path) }}');background-repeat:no-repeat;background-position:center;background-size:contain"></span>
            @endif
            <span style="flex:1;font-size:13.5px;line-height:1.4">{{ $item->name }}<br><span style="color:#9B9B9B;font-size:12px">× {{ $item->quantity }}</span></span>
            <span style="font-size:13.5px">{{ mad($item->line_total_cents) }}</span>
          </div>
        @endforeach

        <div style="padding:20px 24px;display:flex;justify-content:space-between;font-size:16px;border-bottom:1px solid #E6E6E6">
          <span>{{ $order->payment_status === 'paye' ? 'Payé' : 'À régler à la livraison' }}</span>
          <span>{{ mad($order->total_cents) }}</span>
        </div>

        <div style="padding:22px 24px">
          <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:18px">Historique</div>
          <div style="display:grid;gap:14px">
            @foreach ($order->events as $event)
              <div style="display:grid;grid-template-columns:10px 1fr auto;gap:14px;align-items:start">
                <span style="width:8px;height:8px;border-radius:50%;background:#14120F;margin-top:6px"></span>
                <span style="font-size:14px;color:#14120F">{{ $event->label }}</span>
                <span style="font-size:12px;color:#9B9B9B;white-space:nowrap">{{ $event->created_at->format('d/m/Y H:i') }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>

      <p style="margin:26px 0 0;font-size:13px;color:#6B6B6B">
        Une question sur cette commande ? Écrivez à
        <a href="mailto:{{ \App\Models\Setting::get('store_email', 'contact@skinchemists.ma') }}">{{ \App\Models\Setting::get('store_email', 'contact@skinchemists.ma') }}</a>
        en indiquant le numéro {{ $order->number }}.
      </p>
    @endif
  </main>
@endsection
