<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OrderPlacedMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->order->typeLabel().' '.$this->order->number.' · '.$this->order->business_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-placed');
    }
}
