<?php

namespace App\Mail;

use App\Models\Dealer;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RegistrySubmittedMail extends Mailable
{
    public function __construct(public Dealer $dealer) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Nuova anagrafica dealer: '.$this->dealer->business_name);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.registry-submitted');
    }
}
