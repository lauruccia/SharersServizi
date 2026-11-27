@extends('layouts.area')
@section('title', 'Area manager: richieste')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Richieste di registrazione</h1>
<p>Verifica i dati e decidi: l'email con le credenziali parte solo dopo l'approvazione.</p>

<nav class="ef-tabs" aria-label="Stato delle richieste">
    @foreach (['in_attesa' => 'In attesa', 'approvata' => 'Approvate', 'rifiutata' => 'Rifiutate'] as $key => $label)
        <a class="ef-tab" href="{{ route('manager.requests', ['stato' => $key]) }}" @if ($status === $key) aria-current="page" @endif>{{ $label }} ({{ $counts[$key] ?? 0 }})</a>
    @endforeach
</nav>

@if ($requests->isEmpty())
    <div class="ef-empty">Nessuna richiesta in questo elenco.</div>
@else
<div class="ef-table-wrap">
<table class="ef-table">
    <thead><tr><th>Videocall</th><th>Profilo</th><th>Nome</th><th>Regione e zona</th><th>Contatti</th><th>{{ $status === 'in_attesa' ? 'Azioni' : 'Esito' }}</th></tr></thead>
    <tbody>
    @foreach ($requests as $r)
        <tr>
            <td>
                <strong>{{ $r->meeting_at->isoFormat('ddd D MMM') }}</strong><br>ore {{ $r->meeting_at->format('H:i') }}<br>
                <span class="ef-muted">con {{ $r->manager?->name ?? '—' }}</span>
                @if ($r->meet_url)<br><a class="ef-link" href="{{ $r->meet_url }}" target="_blank" rel="noopener">Apri Meet</a>@endif
            </td>
            <td><span class="ef-badge">{{ $r->isDealer() ? 'Dealer' : 'Agente' }}</span></td>
            <td>
                <strong>{{ $r->displayName() }}</strong>
                @if ($r->isDealer())<br>{{ $r->name }}<br><span class="ef-muted">{{ $r->activity_type }}</span>@endif
                @if ($r->experience)<details class="ef-details"><summary>Esperienza</summary>{{ $r->experience }}</details>@endif
            </td>
            <td>{{ $r->region }}<br><span class="ef-muted">{{ $r->isDealer() ? $r->municipality : $r->zones }}</span></td>
            <td><a href="mailto:{{ $r->email }}">{{ $r->email }}</a><br><a href="tel:{{ $r->phone }}">{{ $r->phone }}</a><br><span class="ef-muted">ricevuta il {{ $r->created_at->format('d/m/Y') }}</span></td>
            <td style="min-width:240px">
                @if ($status === 'in_attesa')
                    <div class="ef-actions">
                        <form method="post" action="{{ route('manager.requests.approve', $r) }}">@csrf<button class="ef-btn ef-btn--small" type="submit">Approva</button></form>
                        <form method="post" action="{{ route('manager.requests.reject', $r) }}">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Rifiuta</button></form>
                    </div>
                    <details class="ef-details" style="margin-top:10px">
                        <summary>Chiedi informazioni</summary>
                        <form method="post" action="{{ route('manager.requests.info', $r) }}">@csrf
                            <label class="ef-label" for="body-{{ $r->id }}">Messaggio per il candidato</label>
                            <textarea class="ef-input" id="body-{{ $r->id }}" name="body" required></textarea>
                            <button class="ef-btn ef-btn--small ef-btn--ghost" type="submit" style="margin-top:8px">Invia email</button>
                        </form>
                    </details>
                    @foreach ($r->messages as $m)
                        <div class="ef-muted" style="margin-top:8px">Informazioni chieste il {{ $m->created_at->format('d/m H:i') }}: {{ \Illuminate\Support\Str::limit($m->body, 90) }}</div>
                    @endforeach
                @else
                    {{ $status === 'approvata' ? 'Approvata' : 'Rifiutata' }} da {{ $r->decider?->name ?? '—' }}<br>
                    <span class="ef-muted">{{ $r->decided_at?->format('d/m/Y H:i') }}</span>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif
@endsection
