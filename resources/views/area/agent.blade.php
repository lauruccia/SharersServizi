@extends('layouts.area')
@section('nav')@include('partials.role-nav')@endsection
@section('title', 'Area agente')

@php $canOrder = true; $incomplete = $dealers->filter(fn ($d) => $d->registryState() === 'da_completare'); @endphp

@section('content')
<h1>Buongiorno, {{ auth()->user()->name }}</h1>
<p class="ef-muted">Vedi solo il tuo portafoglio. I dati di produzione compariranno dopo il primo caricamento del back office.</p>

@include('partials.area-tabs', ['route' => 'agent.area'])
<p><a class="ef-link" href="{{ route('orders.index') }}">Ordini del tuo portafoglio</a></p>

@if ($tab === 'optima')
    <div class="ef-actions">
        <a class="ef-btn" href="{{ route('orders.create', 'optima') }}">Ordina adesso</a>
        <a class="ef-btn ef-btn--ghost" href="{{ config('effepi.links.comparatore') }}" target="_blank" rel="noopener">Comparatore bollette</a>
        <a class="ef-btn ef-btn--ghost" href="{{ route('coming-soon', 'invia-contratto') }}">Invia contratto al back office</a>
    </div>

    <div class="ef-cols2 ef-block">
        <div class="ef-card"><div class="ef-label">Guadagno previsto, in base all'avanzamento di oggi</div><p class="ef-kpi ef-kpi--good">— €</p></div>
        <div class="ef-card"><div class="ef-label">Rischi di perdere, se non raggiungi il target</div><p class="ef-kpi ef-kpi--risk">— €</p></div>
    </div>

    <div class="ef-block">
        <h2>Il tuo target personale</h2>
        <div class="ef-cols3">
            @foreach (['SIM', 'Luce e gas', 'Fibra'] as $label)
                <div class="ef-card ef-card--flat"><div class="ef-label">{{ $label }}</div><div class="ef-kpi-small">— / —</div></div>
            @endforeach
        </div>
    </div>

    <div class="ef-block">
        <h2>Avanzamento giornaliero dei tuoi dealer</h2>
        @if ($incomplete->isNotEmpty())
            <div class="ef-alert ef-alert--warn">{{ $incomplete->count() === 1 ? 'Un dealer ha' : $incomplete->count().' dealer hanno' }} l'anagrafica da completare: finché manca non {{ $incomplete->count() === 1 ? 'può' : 'possono' }} ordinare. Compilala dalla tabella qui sotto.</div>
        @endif
        @if ($dealers->isEmpty())
            <div class="ef-empty">Non hai ancora dealer assegnati. Te li assegna il back office, oppure puoi inserirne uno nuovo.</div>
        @else
            <div class="ef-table-wrap"><table class="ef-table">
                <thead><tr><th>Dealer</th><th>Comune</th><th>SIM oggi / mese</th><th>Luce e gas oggi / mese</th><th>Fibra oggi / mese</th><th>Anagrafica</th></tr></thead>
                <tbody>
                @foreach ($dealers as $dealer)
                    <tr>
                        <td><strong>{{ $dealer->business_name }}</strong></td>
                        <td>{{ $dealer->municipality }}</td>
                        <td>— / —</td><td>— / —</td><td>— / —</td>
                        <td>
                            @if ($dealer->registryState() === 'completa')<span class="ef-badge ef-badge--ok">completa</span>
                            @elseif ($dealer->registryState() === 'in_verifica')<a class="ef-badge" href="{{ route('registry.create', $dealer) }}">in verifica</a>
                            @else<a class="ef-btn ef-btn--small" href="{{ route('registry.create', $dealer) }}">Completa</a>@endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </div>

    <div class="ef-cols3 ef-block">
        <div class="ef-card"><h3>File punto vendita</h3><p class="ef-muted">In caricamento.</p></div>
        <div class="ef-card"><h3>Piano compensi dei negozi</h3><p class="ef-muted">Verrà pubblicato dal back office.</p></div>
        <div class="ef-card"><h3>Nuova anagrafica</h3><p>Inserisci un nuovo dealer.</p><a class="ef-btn ef-btn--small" href="{{ route('registry.create') }}">Nuova anagrafica dealer</a></div>
    </div>

    @include('partials.materials')

@elseif ($tab === 'sharers')
    <div class="ef-block"><h2>Clienti che hanno aderito</h2>@include('partials.pending-data')</div>
    <div class="ef-block"><h2>Ordini prodotti dai siti</h2>@include('partials.pending-data')</div>

@elseif ($tab === 'rapicom')
    <div class="ef-block"><h2>Clienti assegnati</h2>@include('partials.pending-data')</div>
    <div class="ef-block"><h2>Ordini</h2>@include('partials.pending-data')</div>

@elseif ($tab === 'altri')
    @include('partials.tab-altri')

@else
    @include('partials.tab-promo')
@endif
@endsection
