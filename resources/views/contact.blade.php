@extends('layouts.app')

@section('title', 'Nous contacter — SkinChemists Maroc')
@section('description', "Une question sur un produit, une commande ou une livraison ? L'équipe SkinChemists Maroc vous répond.")

@section('content')
  @php
    $field = 'width:100%;border:1px solid #E6E6E6;padding:13px 14px;font-size:14px;color:#14120F;outline:none;background:#FFFFFF';
    $label = 'display:block;font-size:10px;letter-spacing:0.18em;text-transform:uppercase;color:#9B9B9B;margin-bottom:8px';

    $storeEmail = \App\Models\Setting::get('store_email', 'contact@skinchemists.ma');
    $storePhone = \App\Models\Setting::get('store_phone');
    // Moroccan customers overwhelmingly message rather than call.
    $whatsapp = $storePhone ? preg_replace('/[^0-9]/', '', str_replace('+', '', $storePhone)) : null;
  @endphp

  <main class="sc-wrap" style="max-width:1100px;margin:0 auto;padding:52px 40px 90px">
    <div style="font-size:11.5px;color:#6B6B6B;margin-bottom:26px">
      <a href="{{ route('home') }}" style="color:#6B6B6B">Accueil</a> / <span style="color:#14120F">Nous contacter</span>
    </div>

    <h1 class="sc-h1" style="font-family:'Montserrat',sans-serif;font-weight:300;letter-spacing:-0.015em;font-size:38px;margin:0 0 12px">Nous contacter</h1>
    <p style="margin:0 0 40px;color:#6B6B6B;font-size:15px;max-width:560px">
      Une question sur un soin, une commande en cours ou une livraison ? Écrivez-nous, nous répondons sous 24 h ouvrées.
    </p>

    @if (session('sent'))
      <div style="background:#EAF0EA;color:#2F5A38;padding:20px 22px;margin-bottom:32px;font-size:14.5px;line-height:1.6">
        <strong>Message bien reçu.</strong><br>
        Nous vous répondons sous 24 h ouvrées. Pour une commande en cours, vous pouvez aussi
        <a href="{{ route('tracking') }}" style="color:#2F5A38;text-decoration:underline">suivre son avancement</a>.
      </div>
    @endif

    @if ($errors->any())
      <div style="background:#F7E9E7;color:#A83A30;padding:16px 18px;margin-bottom:26px;font-size:13.5px">
        <ul style="margin:0;padding-left:18px">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="sc-stack" style="display:grid;grid-template-columns:1fr 320px;gap:50px;align-items:start">
      <form method="POST" action="{{ route('contact.store') }}">
        @csrf

        {{-- Honeypot: hidden from people, irresistible to bots. --}}
        <div style="position:absolute;left:-9999px" aria-hidden="true">
          <label>Site web<input type="text" name="website" tabindex="-1" autocomplete="off" /></label>
        </div>

        <div style="margin-bottom:16px">
          <label style="{{ $label }}" for="name">Nom</label>
          <input id="name" name="name" value="{{ old('name') }}" required style="{{ $field }}" />
        </div>

        <div class="sc-stack-tight" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <div>
            <label style="{{ $label }}" for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" style="{{ $field }}" />
          </div>
          <div>
            <label style="{{ $label }}" for="phone">Téléphone</label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="06 61 22 84 10" style="{{ $field }}" />
          </div>
        </div>
        <p style="margin:-6px 0 20px;font-size:12px;color:#9B9B9B">Laissez au moins l'un des deux pour que nous puissions vous répondre.</p>

        <div class="sc-stack-tight" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
          <div>
            <label style="{{ $label }}" for="subject">Sujet</label>
            <select id="subject" name="subject" required style="{{ $field }}">
              @foreach ($subjects as $value => $text)
                <option value="{{ $value }}" @selected(old('subject') === $value)>{{ $text }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label style="{{ $label }}" for="order_number">N° de commande (facultatif)</label>
            <input id="order_number" name="order_number" value="{{ old('order_number') }}" placeholder="SCM-1043" style="{{ $field }}" />
          </div>
        </div>

        <div style="margin-bottom:24px">
          <label style="{{ $label }}" for="message">Message</label>
          <textarea id="message" name="message" rows="7" required style="{{ $field }}">{{ old('message') }}</textarea>
        </div>

        <button type="submit" class="sc-cta-full"
                style="background:#14120F;color:#FFFFFF;border:0;padding:16px 34px;cursor:pointer;font-size:10.5px;letter-spacing:0.16em;text-transform:uppercase;font-weight:500">
          Envoyer le message
        </button>
      </form>

      <aside style="display:grid;gap:1px;background:#E6E6E6;border:1px solid #E6E6E6">
        <div style="background:#FFFFFF;padding:24px">
          <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:12px">Écrire</div>
          <a href="mailto:{{ $storeEmail }}" style="font-size:14.5px;color:#14120F;word-break:break-word">{{ $storeEmail }}</a>
        </div>

        @if ($storePhone)
          <div style="background:#FFFFFF;padding:24px">
            <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:12px">Appeler</div>
            <a href="tel:{{ preg_replace('/\s/', '', $storePhone) }}" style="font-size:14.5px;color:#14120F">{{ $storePhone }}</a>
            @if ($whatsapp)
              <div style="margin-top:12px">
                <a href="https://wa.me/{{ $whatsapp }}" target="_blank" rel="noopener noreferrer"
                   style="font-size:13px;color:#3F6B45;text-decoration:underline">Écrire sur WhatsApp</a>
              </div>
            @endif
          </div>
        @endif

        <div style="background:#FFFFFF;padding:24px">
          <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:12px">Une commande en cours ?</div>
          <p style="margin:0 0 12px;font-size:13.5px;color:#6B6B6B;line-height:1.6">
            Le suivi est immédiat avec votre numéro de commande et votre téléphone.
          </p>
          <a href="{{ route('tracking') }}" style="font-size:13px;color:#14120F;border-bottom:1px solid #14120F;padding-bottom:2px">Suivre ma commande</a>
        </div>

        <div style="background:#FFFFFF;padding:24px;font-size:13px;color:#6B6B6B;line-height:1.7">
          <div style="font-size:10px;letter-spacing:0.2em;text-transform:uppercase;color:#9B9B9B;margin-bottom:12px">Bon à savoir</div>
          Paiement à la livraison partout au Maroc<br>
          Livraison offerte dès {{ mad($freeShippingThreshold) }}<br>
          Distributeur agréé
        </div>
      </aside>
    </div>
  </main>
@endsection
