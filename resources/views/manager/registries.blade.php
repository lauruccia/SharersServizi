@extends('layouts.area')
@section('title', 'Area manager: anagrafiche')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Anagrafiche dealer</h1>
<p>Le anagrafiche inviate dagli agenti. Verifica dati e documenti, poi attiva il dealer: da quel momento può ordinare.</p>

<div class="ef-block">
<h3>Da verificare ({{ $pending->count() }})</h3>
@forelse ($pending as $dealer)
    <div class="ef-card" style="margin-bottom:16px">
        <div class="ef-band">
            <div><h3 style="margin:0">{{ $dealer->business_name }}</h3>
                <span class="ef-muted">Inviata il {{ $dealer->registry_submitted_at->format('d/m/Y H:i') }} da {{ $dealer->agent?->user?->name ?? '—' }}
                · {{ $dealer->user ? 'ha già un accesso' : 'riceverà le credenziali all\'attivazione' }}</span></div>
            <form method="post" action="{{ route('manager.registries.activate', $dealer) }}">@csrf<button class="ef-btn" type="submit">Attiva il dealer</button></form>
        </div>
        <div class="ef-cols3" style="margin-top:16px">
            <div><div class="ef-label">Partita IVA</div>{{ $dealer->vat_number }}<div class="ef-label" style="margin-top:10px">Codice SDI</div>{{ $dealer->sdi_code }}<div class="ef-label" style="margin-top:10px">IBAN</div>{{ $dealer->iban }}</div>
            <div><div class="ef-label">Sede legale</div>{{ $dealer->legal_address }}<div class="ef-label" style="margin-top:10px">Sede operativa</div>{{ $dealer->operating_address }}<div class="ef-label" style="margin-top:10px">PEC</div>{{ $dealer->pec }}</div>
            <div><div class="ef-label">Email</div>{{ $dealer->email }}<div class="ef-label" style="margin-top:10px">Cellulare legale rappresentante</div>{{ $dealer->legal_rep_mobile }}<div class="ef-label" style="margin-top:10px">Produttività mensile prevista</div>SIM {{ $dealer->expected_sim }} · Energy {{ $dealer->expected_energy }} · Fibra {{ $dealer->expected_fiber }}</div>
        </div>
        <p style="margin-top:14px"><span class="ef-label">Aliquota di fatturazione</span>Il dealer dichiara {{ \App\Models\Dealer::BILLING_REGIMES[$dealer->billing_regime] ?? '—' }}.</p>
        <div class="ef-actions">
            @foreach ($dealer->documents as $document)
                <a class="ef-btn ef-btn--small ef-btn--ghost" href="{{ route('manager.registries.document', $document) }}" target="_blank" rel="noopener">{{ $document->label() }}</a>
            @endforeach
        </div>
    </div>
@empty
    <div class="ef-empty">Nessuna anagrafica da verificare.</div>
@endforelse
</div>

@if ($completed->isNotEmpty())
<div class="ef-block">
<h3>Dealer attivi</h3>
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Negozio</th><th>Partita IVA</th><th>Agente</th><th>Attivato il</th><th>Documenti</th></tr></thead>
    <tbody>
    @foreach ($completed as $dealer)
        <tr><td><strong>{{ $dealer->business_name }}</strong></td><td>{{ $dealer->vat_number ?? '—' }}</td><td>{{ $dealer->agent?->user?->name ?? '—' }}</td><td>{{ $dealer->registry_completed_at->format('d/m/Y') }}</td>
            <td>@foreach ($dealer->documents as $document)<a class="ef-link" href="{{ route('manager.registries.document', $document) }}" target="_blank" rel="noopener">{{ $document->label() }}</a>@if (! $loop->last) · @endif @endforeach</td></tr>
    @endforeach
    </tbody>
</table></div>
</div>
@endif
@endsection
