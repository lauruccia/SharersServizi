@extends('layouts.area')
@section('title', 'Area manager: segnalazioni')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Segnalazioni</h1>
<p>Prima quelle in attesa di risposta.</p>
@if ($tickets->isEmpty())
    <div class="ef-empty">Nessuna segnalazione.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Aggiornata</th><th>Oggetto</th><th>Da</th><th>Dealer</th><th>Tipo</th><th>Stato</th></tr></thead>
    <tbody>
    @foreach ($tickets as $t)
        <tr>
            <td>{{ $t->last_message_at->format('d/m/Y H:i') }}</td>
            <td><a class="ef-link" href="{{ route('tickets.show', $t) }}">{{ $t->subject }}</a></td>
            <td>{{ $t->user->name }}</td>
            <td>{{ $t->dealer?->business_name ?? '—' }}</td>
            <td>{{ $t->categoryLabel() }}</td>
            <td><span class="ef-badge {{ $t->status === 'open' ? 'ef-badge--warn' : ($t->status === 'answered' ? 'ef-badge--ok' : '') }}">{{ $t->statusLabel() }}</span></td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
@endsection
