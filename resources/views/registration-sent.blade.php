@extends('layouts.public')
@section('title', 'Richiesta inviata')
@section('noindex', '1')

@section('content')
<section class="ef-section">
    <div class="ef-wrap ef-narrow">
        <h1>Abbiamo ricevuto la tua richiesta</h1>
        @if ($meetingAt)
            <p>Ti aspettiamo su Google Meet <strong>{{ $meetingAt->isoFormat('dddd D MMMM YYYY') }} alle {{ $meetingAt->format('H:i') }}</strong>, per una videocall di circa 30 minuti: riceverai un'email con il link per collegarti.</p>
        @else
            <p>Riceverai un'email con data, ora e link della videocall.</p>
        @endif
        <p>Dopo l'approvazione del back office riceverai anche le credenziali di accesso. Fino ad allora l'area riservata non è accessibile.</p>
        <p>Per informazioni scrivi a <a href="mailto:{{ config('effepi.mail.backoffice') }}">{{ config('effepi.mail.backoffice') }}</a>.</p>
        <a class="ef-btn" href="{{ route('home') }}">Torna alla home</a>
    </div>
</section>
@endsection
