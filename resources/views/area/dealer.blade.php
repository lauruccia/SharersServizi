@extends('layouts.area')
@section('nav')@include('partials.role-nav')@endsection
@section('title', 'Area dealer')

@php $canOrder = $dealer->canOrder(); @endphp

@section('content')
<h1>Buongiorno, {{ $dealer->business_name }}</h1>
<p class="ef-muted">I dati di produzione compariranno dopo il primo caricamento del back office.</p>

@include('partials.area-tabs', ['route' => 'dealer.area'])
<p><a class="ef-link" href="{{ route('orders.index') }}">I tuoi ordini</a></p>

@if ($tab === 'optima')
    <div class="ef-actions">
        @if ($canOrder)<a class="ef-btn" href="{{ route('orders.create', 'optima') }}">Ordina adesso</a>@endif
        <a class="ef-btn ef-btn--ghost" href="{{ config('effepi.links.optimaster') }}" target="_blank" rel="noopener">Vai a Optimaster</a>
        <a class="ef-btn ef-btn--ghost" href="{{ config('effepi.links.comparatore') }}" target="_blank" rel="noopener">Comparatore bollette</a>
        <a class="ef-btn ef-btn--ghost" href="{{ route('coming-soon', 'invia-contratto') }}">Invia contratto al back office</a>
    </div>
    @unless ($canOrder)<div style="margin-top:16px">@include('partials.cannot-order')</div>@endunless

    <div class="ef-cols2 ef-block">
        <div class="ef-card"><div class="ef-label">Stai guadagnando, in base all'avanzamento</div><p class="ef-kpi ef-kpi--good">— €</p></div>
        <div class="ef-card"><div class="ef-label">Guadagni raggiungendo il target mensile pattuito</div><p class="ef-kpi">— €</p></div>
    </div>

    <div class="ef-block">
        <h2>I tuoi target del mese</h2>
        <div class="ef-cols3">
            @foreach (['SIM', 'Luce e gas', 'Fibra'] as $label)
                <div class="ef-card ef-card--flat"><div class="ef-label">{{ $label }}</div><div class="ef-kpi-small">— / —</div></div>
            @endforeach
        </div>
    </div>

    <div class="ef-block">
        <h2>Gare extra</h2>
        @include('partials.pending-data', ['text' => 'Nessuna gara in corso.'])
    </div>

    <div class="ef-block">
        <h2>Il tuo avanzamento di oggi</h2>
        <div class="ef-cols4">
            @foreach (['Luce', 'Gas', 'Fibra', 'SIM'] as $label)
                <div class="ef-card ef-card--flat"><div class="ef-label">{{ $label }}</div><div class="ef-kpi-small">—</div><div class="ef-muted">— nel mese</div></div>
            @endforeach
        </div>
    </div>

    <div class="ef-cols2 ef-block">
        <div class="ef-card"><h3>Piano compensi del mese</h3><p class="ef-muted">Verrà pubblicato dal back office.</p></div>
        <div class="ef-card"><h3>Apri una segnalazione</h3><p class="ef-muted">Ti risponde il back office, anche via email.</p><a class="ef-btn ef-btn--small" href="{{ route('tickets.create') }}">Nuova segnalazione</a> <a class="ef-link" href="{{ route('tickets.index') }}">Le tue segnalazioni</a></div>
    </div>

    @include('partials.materials')

@elseif ($tab === 'sharers')
    <div class="ef-actions">
        <a class="ef-btn ef-btn--ghost" href="{{ config('effepi.links.sharers') }}" target="_blank" rel="noopener">Vai su sharers.it</a>
        @if ($canOrder)<a class="ef-btn" href="{{ route('orders.create', 'altro') }}">Ordina adesso</a>@endif
    </div>
    <div class="ef-block">
        <h2>Il tuo sito Sharers</h2>
        <div class="ef-cols3">
            @foreach (['Iscritti al tuo sito', 'Target iscrizioni', 'Ordini del mese'] as $label)
                <div class="ef-card ef-card--flat"><div class="ef-label">{{ $label }}</div><div class="ef-kpi-small">—</div></div>
            @endforeach
        </div>
    </div>
    <div class="ef-block"><h2>Ordini dal tuo sito</h2>@include('partials.pending-data')</div>

@elseif ($tab === 'rapicom')
    <div class="ef-actions"><a class="ef-btn ef-btn--ghost" href="{{ config('effepi.links.rapicom') }}" target="_blank" rel="noopener">Vai su rapicom.it</a></div>
    <div class="ef-block">
        <h2>Grafiche dei servizi Rapicom</h2>
        <p>Scarica le grafiche da esporre in negozio e da pubblicare sui tuoi canali.</p>
        <div class="ef-card ef-card--flat">@include('partials.resource-list', ['items' => \App\Models\Resource::query()->where('kind', 'rapicom')->orderBy('position')->orderBy('id')->get()])</div>
    </div>

@elseif ($tab === 'altri')
    @include('partials.tab-altri')

@else
    @include('partials.tab-promo')
@endif
@endsection
