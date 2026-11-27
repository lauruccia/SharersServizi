@extends('emails.layout')
@section('content')
<p>Nuova richiesta di registrazione come <strong>{{ $registration->isDealer() ? 'dealer' : 'agente' }}</strong>.</p>
<p><strong>{{ $registration->displayName() }}</strong>@if ($registration->isDealer()) · referente {{ $registration->name }}@endif<br>
{{ $registration->region }} · {{ $registration->isDealer() ? $registration->municipality : $registration->zones }}<br>
{{ $registration->phone }} · {{ $registration->email }}</p>
<p>Videocall: <strong>{{ $registration->meeting_at->isoFormat('dddd D MMMM YYYY') }} alle {{ $registration->meeting_at->format('H:i') }}</strong></p>
<p><a href="{{ route('manager.requests') }}">Apri le richieste nell'area manager</a></p>
@endsection
