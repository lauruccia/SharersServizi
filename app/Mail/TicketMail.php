<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Notifica di segnalazione: al back office quando scrive l'utente, all'utente quando risponde il back office. */
class TicketMail extends Mailable
{
    public function __construct(public Ticket $ticket, public TicketMessage $message, public bool $toStaff) {}

    public function envelope(): Envelope
    {
        $prefix = $this->toStaff ? 'Segnalazione' : 'Risposta alla tua segnalazione';

        return new Envelope(subject: $prefix.' #'.$this->ticket->id.': '.$this->ticket->subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket');
    }
}
