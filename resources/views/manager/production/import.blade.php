@extends('layouts.area')
@section('title', 'Area manager: import produzione')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Import produzione (ReportDealer)</h1>
<p>Carica il file ReportDealer di Optima (.xls, .xlsx o .csv). Ogni riga è un contratto: ricaricare lo stesso file, o uno più recente, aggiorna le righe già presenti (chiave "Id Contratto") senza duplicarle e conserva i flag "forza conteggio".</p>

<form class="ef-card" method="post" action="{{ route('manager.production.import.store') }}" enctype="multipart/form-data">@csrf
    <div class="ef-field">
        <label for="file">File ReportDealer</label>
        <input class="ef-input" id="file" type="file" name="file" accept=".xls,.xlsx,.csv" required>
        @error('file')<div class="ef-muted" style="color:var(--red)">{{ $message }}</div>@enderror
    </div>
    <button class="ef-btn" type="submit">Importa e calcola</button>
</form>

<details class="ef-details ef-block"><summary>Come vengono lette le righe</summary>
<ul>
    <li><strong>Data di stipula</strong> è la data che fa fede per mese, trimestre e anno.</li>
    <li><strong>Servizi:</strong> MOBILE = SIM, ENERGIA = luce, GAS = gas, ADSL = fibra. Con suffisso <code>_C</code> consumer, senza business. VOIP è un'opzione dell'ADSL e non si conteggia. H24, Tutela legale e Videoconsulti si importano ma non hanno compenso.</li>
    <li><strong>Attivo</strong> = "OK Attivazione". <strong>Non conteggiati</strong>: motivo "QCC KO" o "KO Solvibilità", contratti cestinati, CheckCard negativa. Gli altri stati sono <strong>in lavorazione</strong> (mostrati a parte, non pagati salvo diversa impostazione nel piano compensi).</li>
    <li>I dealer si riconoscono dal loro "Id Dealer" (codice nell'elenco Dealer); se non c'è, si collegano per nome o si creano. Area e ambassador del file riempiono solo i campi vuoti.</li>
    <li>Dati del cliente: si conserva solo il nome; codice fiscale e telefono non vengono salvati.</li>
</ul></details>

<h3 class="ef-block">Ultimi import</h3>
@if ($imports->isEmpty())
    <div class="ef-empty">Nessun import eseguito.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Quando</th><th>Chi</th><th>File</th><th>Righe</th><th>Nuove</th><th>Aggiornate</th><th>Scartate</th><th>Nuovi dealer</th><th>Stipule dal – al</th></tr></thead>
    <tbody>
    @foreach ($imports as $i)
        <tr><td>{{ $i->created_at?->format('d/m/Y H:i') }}</td><td>{{ $i->user?->name }}</td><td>{{ $i->filename }}</td>
            <td>{{ $i->rows_read }}</td><td>{{ $i->rows_created }}</td><td>{{ $i->rows_updated }}</td><td>{{ $i->rows_skipped }}</td><td>{{ $i->dealers_created }}</td>
            <td>{{ $i->period_from?->format('d/m/Y') }} – {{ $i->period_to?->format('d/m/Y') }}</td></tr>
        @if (! empty($i->notes))
        <tr><td></td><td colspan="8">
            @foreach (($i->notes['skipped'] ?? []) as $n)<div class="ef-muted">{{ $n }}</div>@endforeach
            @if (! empty($i->notes['protected']))<div class="ef-muted">{{ $i->notes['protected'] }} contratti corretti a mano non sono stati sovrascritti.</div>@endif
            @if (! empty($i->notes['new_dealers']))<div class="ef-muted">Dealer creati: {{ implode(', ', array_slice($i->notes['new_dealers'], 0, 15)) }}@if (count($i->notes['new_dealers']) > 15) … (+{{ count($i->notes['new_dealers']) - 15 }})@endif. Assegnali agli agenti da <a class="ef-link" href="{{ route('manager.network') }}">Rete</a>.</div>@endif
            @if (! empty($i->notes['linked_by_name']))<div class="ef-muted">Collegati per nome a dealer esistenti: {{ implode(', ', $i->notes['linked_by_name']) }}</div>@endif
        </td></tr>
        @endif
    @endforeach
    </tbody>
</table></div>
@endif
@endsection
