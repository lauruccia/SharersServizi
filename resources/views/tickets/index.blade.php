@extends('layouts.area')
@section('title', 'Segnalazioni')

@section('content')
<p><a class="ef-link" href="{{ route(auth()->user()->homeRoute()) }}">← Torna all'area riservata</a></p>
<h1>Segnalazioni</h1>
<p><a class="ef-btn" href="{{ route('tickets.create') }}">Nuova segnalazione</a></p>
@if ($tickets->isEmpty())
    <div class="ef-empty">Non hai ancora aperto segnalazioni.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Ultimo aggiornamento</th><th>Oggetto</th><th>Tipo</th><th>Dealer</th><th>Stato</th></tr></thead>
    <tbody>
    @foreach ($tickets as $t)
        <tr>
            <td>{{ $t->last_message_at->format('d/m/Y H:i') }}</td>
            <td><a class="ef-link" href="{{ route('tickets.show', $t) }}">{{ $t->subject }}</a></td>
            <td>{{ $t->categoryLabel() }}</td>
            <td>{{ $t->dealer?->business_name ?? '—' }}</td>
            <td><span class="ef-badge {{ $t->status === 'answered' ? 'ef-badge--ok' : ($t->status === 'open' ? 'ef-badge--warn' : '') }}">{{ $t->statusLabel() }}</span></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
@endsection
