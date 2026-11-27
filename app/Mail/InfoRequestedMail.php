<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InfoRequestedMail extends Mailable
{
    public function __construct(public RegistrationRequest $registration, public string $body) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Ci serve qualche informazione in più');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.info-requested');
    }
}
