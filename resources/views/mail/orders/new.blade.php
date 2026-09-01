<x-mail::message>
# Nouvelle commande {{ $order->number }}

**{{ mad($order->total_cents) }}** — paiement à la livraison — {{ $order->city }}

À confirmer par téléphone avant préparation.

<x-mail::panel>
**{{ $order->customerName() }}**<br>
{{ $order->phone }}@if ($order->email) · {{ $order->email }}@endif<br>
{{ $order->address }}<br>
{{ $order->city }}@if ($order->zip) · {{ $order->zip }}@endif
@if ($order->note)

Note du client : {{ $order->note }}
@endif
</x-mail::panel>

<x-mail::table>
| Produit | SKU | Qté | Total |
|:--------|:----|:---:|------:|
@foreach ($order->items as $item)
| {{ $item->name }} | {{ $item->sku }} | {{ $item->quantity }} | {{ mad($item->line_total_cents) }} |
@endforeach
</x-mail::table>

<x-mail::table>
|  |  |
|:-----|------:|
| Sous-total | {{ mad($order->subtotal_cents) }} |
@if ($order->discount_cents > 0)
| Remise{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }} | −{{ mad($order->discount_cents) }} |
@endif
| Livraison ({{ $order->shipping_method }}) | {{ $order->shipping_cents === 0 ? 'Offerte' : mad($order->shipping_cents) }} |
| **Total** | **{{ mad($order->total_cents) }}** |
</x-mail::table>

<x-mail::button :url="url('/admin/orders/'.$order->id)">
Ouvrir dans l'administration
</x-mail::button>
</x-mail::message>
