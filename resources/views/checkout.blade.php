@extends('layouts.app')

@section('title', 'Commande — SkinChemists Maroc')

@section('content')
  @php
    $field = 'width:100%;border:1px solid #E6E6E6;padding:13px 14px;font-size:14px;color:#14120F;outline:none;background:#FFFFFF';
    $label = 'display:block;font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#9B9B9B;margin-bottom:8px';
  @endphp

  <section class="sc-wrap" style="max-width:1100px;margin:0 auto;padding:52px 40px 74px">
    <h1 class="sc-h1" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:34px;margin:0 0 30px">Finaliser la commande</h1>

    @if ($errors->any())
      <div style="background:#F7E9E7;color:#A83A30;padding:16px 18px;margin-bottom:26px;font-size:13.5px">
        <ul style="margin:0;padding-left:18px">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    {{-- The delivery options depend on the city, and the city is picked on this
         page, so the labels are driven client-side. The prices below are only
         what the customer is quoted: PlaceOrder recharges from App\Support\
         Shipping on submit, so a tampered form cannot buy cheaper delivery. --}}
    <form method="POST" action="{{ route('checkout.store') }}"
          class="sc-stack" style="display:grid;grid-template-columns:1fr 340px;gap:50px;align-items:start"
          x-data="{
              city: '{{ old('city', $cities[0]) }}',
              method: '{{ old('shipping_method', 'standard') }}',
              get sameDay() { return this.city.toLowerCase().includes('casa') },
              get shipping() {
                  if (this.sameDay) return {{ \App\Support\Shipping::sameDayCityCents() }};
                  if (this.method === 'express') return {{ \App\Support\Shipping::expressCents() }};
                  return {{ $pricing->subtotal - $pricing->discount }} >= {{ \App\Support\Shipping::freeThresholdCents() }}
                      ? 0 : {{ \App\Support\Shipping::standardCents() }};
              },
              money(c) { return (c / 100).toFixed(2).replace('.00', '') + ' MAD' },
          }"
          x-effect="if (sameDay) method = 'standard'">
      @csrf

      {{-- Honeypot: hidden from people, irresistible to bots. --}}
      <div style="position:absolute;left:-9999px" aria-hidden="true">
        <label>Site web<input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
      </div>

      <div>
        <h2 style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin:0 0 18px">Coordonnées</h2>
        <div class="sc-stack-tight" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <div>
            <label style="{{ $label }}" for="first_name">Prénom</label>
            <input id="first_name" name="first_name" value="{{ old('first_name') }}" required style="{{ $field }}" />
          </div>
          <div>
            <label style="{{ $label }}" for="last_name">Nom</label>
            <input id="last_name" name="last_name" value="{{ old('last_name') }}" required style="{{ $field }}" />
          </div>
        </div>

        <div class="sc-stack-tight" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <div>
            <label style="{{ $label }}" for="phone">Téléphone</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" required
                   placeholder="06 61 22 84 10" style="{{ $field }}" />
          </div>
          <div>
            <label style="{{ $label }}" for="email">Email (facultatif)</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" style="{{ $field }}" />
          </div>
        </div>

        <h2 style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin:28px 0 18px">Livraison</h2>
        <div style="margin-bottom:16px">
          <label style="{{ $label }}" for="address">Adresse</label>
          <input id="address" name="address" value="{{ old('address') }}" required style="{{ $field }}" />
        </div>

        <div class="sc-stack-tight" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:22px">
          <div>
            <label style="{{ $label }}" for="city">Ville</label>
            <select id="city" name="city" required x-model="city" style="{{ $field }}">
              @foreach ($cities as $city)
                <option value="{{ $city }}" @selected(old('city') === $city)>{{ $city }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label style="{{ $label }}" for="zip">Code postal (facultatif)</label>
            <input id="zip" name="zip" value="{{ old('zip') }}" style="{{ $field }}" />
          </div>
        </div>

        <div style="display:grid;gap:1px;background:#E6E6E6;border:1px solid #E6E6E6;margin-bottom:22px">
          <label style="background:#FFFFFF;padding:16px 18px;display:flex;gap:12px;align-items:center;cursor:pointer">
            <input type="radio" name="shipping_method" value="standard" x-model="method" />
            <span style="flex:1;font-size:14px">
              <span x-text="sameDay ? 'Livraison à Casablanca' : 'Livraison standard'">Livraison standard</span>
              ·
              <span x-text="sameDay
                  ? 'le jour même si vous commandez avant {{ \App\Support\Shipping::sameDayCutoffHour() }} h'
                  : '2 à 3 jours'">2 à 3 jours</span>
            </span>
            <span style="font-size:13.5px;color:#6B6B6B"
                  x-text="sameDay ? 'Offerte' : 'Offerte dès {{ mad($freeShippingThreshold) }}'">Offerte dès {{ mad($freeShippingThreshold) }}</span>
          </label>

          {{-- Express is sold as a 24h upgrade for the other cities. Casablanca
               is already served the same day, so offering it there would be
               charging for nothing.

               x-show sits on the wrapper, not on the label: Alpine reveals an
               element with style.removeProperty('display'), which deletes the
               author's own display:flex for good. One hide/show cycle and the
               row loses its layout — the radio, the label and the price stop
               sitting on one line. The wrapper has no display to lose. --}}
          <div x-show="! sameDay">
            <label style="background:#FFFFFF;padding:16px 18px;display:flex;gap:12px;align-items:center;cursor:pointer">
              <input type="radio" name="shipping_method" value="express" x-model="method" />
              <span style="flex:1;font-size:14px">Livraison express · 24 h</span>
              <span style="font-size:13.5px;color:#6B6B6B">{{ mad(\App\Support\Shipping::expressCents()) }}</span>
            </label>
          </div>
        </div>

        <h2 style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin:28px 0 18px">Paiement</h2>
        <div style="border:1px solid #14120F;padding:16px 18px;font-size:14px;margin-bottom:22px">
          Paiement à la livraison — vous réglez le montant au coursier à la réception.
        </div>

        <div>
          <label style="{{ $label }}" for="note">Note pour la livraison (facultatif)</label>
          <textarea id="note" name="note" rows="3" style="{{ $field }}">{{ old('note') }}</textarea>
        </div>
      </div>

      <aside class="sc-unstick" style="border:1px solid #E6E6E6;padding:26px;position:sticky;top:110px">
        <h2 style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin:0 0 18px">Votre commande</h2>

        @foreach ($lines as $line)
          @php($product = $line['product'])
          <div style="display:flex;gap:14px;padding-bottom:14px;margin-bottom:14px;border-bottom:1px solid #F4F4F4">
            <span style="width:52px;height:52px;flex:none;background:#FAFAFA;background-image:url('{{ $product->primaryImageUrl() }}');background-repeat:no-repeat;background-position:center;background-size:contain"></span>
            <span style="flex:1;font-size:12.5px;line-height:1.4">{{ $product->name }}<br><span style="color:#9B9B9B">× {{ $line['quantity'] }}</span></span>
            <span style="font-size:13px">{{ mad($product->effectivePriceCents() * $line['quantity']) }}</span>
          </div>
        @endforeach

        <div style="margin:18px 0">
          <label style="{{ $label }}" for="coupon_code">Code promo</label>
          <input id="coupon_code" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="MAROC10" style="{{ $field }}" />
        </div>

        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:9px">
          <span style="color:#6B6B6B">Sous-total</span><span>{{ mad($pricing->subtotal) }}</span>
        </div>
        @if ($pricing->discount > 0)
          <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:9px;color:#3F6B45">
            <span>Remise coffret</span><span>−{{ mad($pricing->discount) }}</span>
          </div>
        @endif
        <div style="display:flex;justify-content:space-between;font-size:13.5px;margin-bottom:16px">
          <span style="color:#6B6B6B">Livraison</span>
          <span x-text="shipping === 0 ? 'Offerte' : money(shipping)">{{ $pricing->shipping === 0 ? 'Offerte' : mad($pricing->shipping) }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:16px;padding-top:16px;border-top:1px solid #E6E6E6;margin-bottom:20px">
          <span>Total</span>
          <span x-text="money({{ $pricing->subtotal - $pricing->discount }} + shipping)">{{ mad($pricing->total) }}</span>
        </div>
        <p style="margin:0 0 18px;font-size:11.5px;color:#9B9B9B;line-height:1.5">
          Le total définitif, remise et livraison comprises, est recalculé au moment de la validation.
        </p>

        <button type="submit" style="width:100%;background:#14120F;color:#FFFFFF;border:0;padding:16px;cursor:pointer;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">
          Confirmer la commande
        </button>
      </aside>
    </form>
  </section>
@endsection
