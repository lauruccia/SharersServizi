<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class CredentialsMail extends Mailable
{
    public function __construct(public User $user, public string $temporaryPassword) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'La tua richiesta è stata approvata');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.credentials');
    }
}
