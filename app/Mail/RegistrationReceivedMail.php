<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class RegistrationReceivedMail extends Mailable
{
    public function __construct(public RegistrationRequest $registration) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Abbiamo ricevuto la tua richiesta');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.registration-received');
    }
}
