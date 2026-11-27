@extends('emails.layout')
@section('content')
<p>Buongiorno {{ $registration->name }},</p>
<p>abbiamo ricevuto la tua richiesta come <strong>{{ $registration->isDealer() ? 'dealer' : 'agente' }}</strong>.</p>
<p>Ti aspettiamo su Google Meet <strong>{{ $registration->meeting_at->isoFormat('dddd D MMMM YYYY') }} alle {{ $registration->meeting_at->format('H:i') }}</strong>, per una videocall di circa 30 minuti.</p>
@if ($registration->meet_url)
    <p>Link per collegarti: <a href="{{ $registration->meet_url }}">{{ $registration->meet_url }}</a></p>
@else
    <p>Riceverai il link per collegarti prima dell'appuntamento.</p>
@endif
<p>Dopo l'approvazione del back office riceverai le credenziali per l'area riservata.</p>
<p>Se non puoi partecipare scrivi a <a href="mailto:{{ config('effepi.mail.backoffice') }}">{{ config('effepi.mail.backoffice') }}</a>.</p>
@endsection
