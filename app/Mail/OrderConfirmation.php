<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Sent to the customer once the order is committed. */
class OrderConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Votre commande {$this->order->number} est confirmée",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.confirmation',
            with: ['order' => $this->order->loadMissing('items')],
        );
    }
}
