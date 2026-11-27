@extends('layouts.area')
@section('title', 'Produzione: '.$dealer->business_name)
@section('nav')@include('partials.role-nav')@endsection

@php($eur = fn (int $c) => \App\Services\ProductionReport::eur($c))
@php($admin = auth()->user()->isBackOffice())
@section('content')
<p><a class="ef-link" href="{{ route('production.index', $range->query()) }}">← Tutti i dealer</a></p>
<h1>{{ $dealer->business_name }}</h1>
<p class="ef-muted">
    @if ($dealer->agent)Agente: {{ $dealer->agent->user->name }} · @endif
    @if ($dealer->area)Area: {{ $dealer->area->name }} · @endif
    @if ($dealer->ambassador)Ambassador: {{ $dealer->ambassador->name }}@endif
</p>

@include('partials.date-filter', ['range' => $range, 'action' => route('production.dealer', $dealer)])

@if ($line)
<div class="ef-cols4 ef-block">
    <div class="ef-card"><div class="ef-label">Attivazioni</div><p class="ef-kpi">{{ $line['derived']['activations'] }}</p><div class="ef-muted">di cui {{ $line['derived']['mnp'] }} MNP</div></div>
    <div class="ef-card"><div class="ef-label">SIM · MNP</div><p class="ef-kpi">{{ $line['metrics']['sim'] }} · {{ $line['metrics']['sim_mnp'] }}</p></div>
    <div class="ef-card"><div class="ef-label">Luce · gas · fibra</div><p class="ef-kpi">{{ $line['metrics']['luce'] }} · {{ $line['metrics']['gas'] }} · {{ $line['metrics']['fibra'] }}</p></div>
    <div class="ef-card"><div class="ef-label">Compensi</div><p class="ef-kpi ef-kpi--good">{{ $eur($line['earnings']['total']) }}</p>
        <div class="ef-muted">SIM {{ $eur($line['earnings']['sim']) }} · fibra {{ $eur($line['earnings']['fibra']) }} · energy {{ $eur($line['earnings']['energy']) }}@if ($line['earnings']['adjust']) · rettifiche {{ $eur($line['earnings']['adjust']) }}@endif</div></div>
</div>

<h3>Mese per mese</h3>
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Mese</th><th>SIM</th><th>MNP</th><th>Luce</th><th>Gas</th><th>Fibra</th><th>Compenso SIM</th><th>Fibra</th><th>Energy</th><th>Rettifiche</th><th>Totale</th></tr></thead>
    <tbody>
    @foreach ($line['months'] as $month => $m)
        <tr><td>{{ \Illuminate\Support\Carbon::createFromFormat('!Y-m', $month)->isoFormat('MMMM YYYY') }}</td>
            <td>{{ $m['metrics']['sim'] }}</td><td>{{ $m['metrics']['sim_mnp'] }}</td><td>{{ $m['metrics']['luce'] }}</td><td>{{ $m['metrics']['gas'] }}</td><td>{{ $m['metrics']['fibra'] }}</td>
            <td>{{ $eur($m['earnings']['sim']) }}</td><td>{{ $eur($m['earnings']['fibra']) }}</td><td>{{ $eur($m['earnings']['energy']) }}</td><td>{{ $m['earnings']['adjust'] ? $eur($m['earnings']['adjust']) : '—' }}</td><td><strong>{{ $eur($m['earnings']['total']) }}</strong></td></tr>
    @endforeach
    </tbody>
</table></div>
@endif

@if ($admin)
<div class="ef-block">
    <h3>Rettifiche ai compensi</h3>
    <p class="ef-muted">Aggiungi o togli un importo a mano per correggere un errore. Compare nel mese indicato e si somma ai compensi calcolati.</p>
    @if ($adjustments->isNotEmpty())
    <div class="ef-table-wrap" style="margin-bottom:14px"><table class="ef-table">
        <thead><tr><th>Mese</th><th>Importo</th><th>Motivo</th><th>Chi</th><th></th></tr></thead>
        <tbody>@foreach ($adjustments as $a)
            <tr><td>{{ ucfirst($a->month->isoFormat('MMMM YYYY')) }}</td><td><strong>{{ $eur($a->amount_cents) }}</strong></td><td>{{ $a->note }}</td><td>{{ $a->author?->name }}</td>
                <td><form method="post" action="{{ route('manager.production.adjust.destroy', $a) }}">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Rimuovi</button></form></td></tr>
        @endforeach</tbody>
    </table></div>
    @endif
    <form class="ef-card" method="post" action="{{ route('manager.production.adjust.store') }}">@csrf
        <input type="hidden" name="dealer_id" value="{{ $dealer->id }}">
        <div class="ef-cols3">
            <div class="ef-field"><label for="adj_month">Mese</label><input class="ef-input" id="adj_month" type="month" name="month" value="{{ $range->from->format('Y-m') }}" required></div>
            <div class="ef-field"><label for="adj_amount">Importo (€, negativo per togliere)</label><input class="ef-input" id="adj_amount" type="number" step="0.01" name="amount" required></div>
            <div class="ef-field"><label for="adj_note">Motivo</label><input class="ef-input" id="adj_note" name="note" required></div>
        </div>
        <button class="ef-btn ef-btn--small" type="submit">Aggiungi la rettifica</button>
    </form>
    <p style="margin-top:14px"><a class="ef-btn ef-btn--ghost" href="{{ route('manager.production.row.create', ['dealer' => $dealer->id]) }}">+ Inserisci un contratto a mano</a></p>
</div>
@endif

<h3 class="ef-block">Contratti del periodo ({{ $rows->count() }})</h3>
@if ($rows->isEmpty())
    <div class="ef-empty">Nessun contratto in questo periodo.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Stipula</th><th>Servizio</th><th>Stato</th><th>Esito</th><th>SIM / MNP</th><th>Offerta</th><th>Pagamento</th><th>Fattura</th><th>Note</th>@if ($admin)<th>Forza</th>@endif</tr></thead>
    <tbody>
    @foreach ($rows as $row)
        @php($o = $row->effectiveOutcome())
        <tr>
            <td>{{ $row->signed_at->format('d/m/Y') }}</td>
            <td>{{ $row->service_raw }}@if ($row->service_type === 'voip')<br><span class="ef-muted">opzione ADSL, non conteggiata</span>@endif</td>
            <td>{{ $row->status }}<br><span class="ef-muted">{{ $row->cc_status ? 'CC: '.$row->cc_status : '' }}</span></td>
            <td><span class="ef-badge {{ $o === 'active' ? 'ef-badge--ok' : ($o === 'pending' ? 'ef-badge--warn' : '') }}">{{ \App\Models\ProductionRow::OUTCOME_LABELS[$o] }}</span>@if ($row->force_count !== null)<br><span class="ef-muted">forzato</span>@endif</td>
            <td>@if ($row->service_type === 'sim'){{ $row->sims_sold }} / {{ $row->sims_portability }}@else — @endif</td>
            <td>{{ $row->offer_code }}</td>
            <td>{{ $row->payment_method }}</td>
            <td>{{ $row->invoice_delivery }}</td>
            <td>{{ $row->discard_reason }} {{ $row->ccn_note }}</td>
            @if ($admin)
            <td>
                <form method="post" action="{{ route('manager.production.force', $row) }}" class="ef-actions">@csrf
                    <select class="ef-input" name="mode" aria-label="Forza calcolo">
                        <option value="auto" @selected($row->force_count === null)>Automatico</option>
                        <option value="count" @selected($row->force_count === true)>Conta sempre</option>
                        <option value="exclude" @selected($row->force_count === false)>Non conta</option>
                    </select>
                    <button class="ef-btn ef-btn--small ef-btn--ghost" type="submit">Salva</button>
                </form>
                <div style="margin-top:6px"><a class="ef-link" href="{{ route('manager.production.row.edit', $row) }}">Correggi</a>
                    <form method="post" action="{{ route('manager.production.row.destroy', $row) }}" class="ef-inline-form" onsubmit="return confirm('{{ $row->is_manual ? 'Eliminare questo contratto manuale?' : 'Escludere questo contratto dai conteggi?' }}')">@csrf <button class="ef-btn ef-btn--small ef-btn--danger" type="submit">{{ $row->is_manual ? 'Elimina' : 'Escludi' }}</button></form></div>
                @if ($row->locked)<span class="ef-badge" title="{{ $row->edit_note }}">{{ $row->is_manual ? 'manuale' : 'corretto a mano' }}</span>@endif
            </td>
            @endif
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
@endsection
