<x-mail::message>
# {{ $contact->subjectLabel() }}

<x-mail::panel>
**{{ $contact->name }}**<br>
@if ($contact->phone){{ $contact->phone }}@endif@if ($contact->phone && $contact->email) · @endif@if ($contact->email){{ $contact->email }}@endif
@if ($contact->order_number)
<br>Commande : **{{ $contact->order_number }}**
@endif
</x-mail::panel>

{{ $contact->message }}

<x-mail::button :url="url('/admin/contact-messages/'.$contact->id)">
Ouvrir dans l'administration
</x-mail::button>
</x-mail::message>
