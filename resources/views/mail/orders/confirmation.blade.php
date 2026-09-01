<x-mail::message>
# Merci {{ $order->first_name }}.

Votre commande **{{ $order->number }}** est bien enregistrée.

Nous vous appelons au **{{ $order->phone }}** pour la confirmer avant expédition.
Vous réglerez **{{ mad($order->total_cents) }}** au coursier, à la réception.

<x-mail::table>
| Produit | Qté | Total |
|:--------|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->quantity }} | {{ mad($item->line_total_cents) }} |
@endforeach
</x-mail::table>

{{-- A table, not stacked lines: Markdown joins consecutive lines into one
     paragraph, which ran the totals together on a single row. --}}
<x-mail::table>
|  |  |
|:-----|------:|
| Sous-total | {{ mad($order->subtotal_cents) }} |
@if ($order->discount_cents > 0)
| Remise | −{{ mad($order->discount_cents) }} |
@endif
| Livraison ({{ $order->shipping_method === 'express' ? 'express' : 'standard' }}) | {{ $order->shipping_cents === 0 ? 'Offerte' : mad($order->shipping_cents) }} |
| **Total à payer** | **{{ mad($order->total_cents) }}** |
</x-mail::table>

#### Livraison

{{ $order->customerName() }}<br>
{{ $order->address }}<br>
{{ $order->city }}@if ($order->zip) · {{ $order->zip }}@endif

<x-mail::button :url="route('tracking')">
Suivre ma commande
</x-mail::button>

Gardez votre numéro **{{ $order->number }}** : il vous sera demandé, avec votre
téléphone, pour suivre la commande.

Merci de votre confiance,
{{ \App\Models\Setting::get('store_name', 'skinChemists Maroc') }}
</x-mail::message>
