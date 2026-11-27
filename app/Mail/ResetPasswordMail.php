<?php

namespace App\Mail;

use App\Models\RegistrationRequest;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ResetPasswordMail extends Mailable
{
    public function __construct(public User $user, public string $token) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Reimposta la tua password');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.reset-password');
    }
}
