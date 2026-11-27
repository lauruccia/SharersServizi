@extends('layouts.area')
@section('title', 'Area capoarea')

@section('nav')@include('partials.role-nav')@endsection

@section('content')
<h1>Buongiorno, {{ auth()->user()->name }}</h1>
<p class="ef-muted">Vedi i tuoi agenti, i loro dealer e i loro ordini, in sola lettura. I dati di chi sta sopra di te non sono visibili.</p>

<div class="ef-cols2 ef-block">
    <div class="ef-card"><div class="ef-label">Agenti</div><p class="ef-kpi">{{ $agents->count() }}</p></div>
    <div class="ef-card"><div class="ef-label">Dealer</div><p class="ef-kpi">{{ $dealers->count() }}</p></div>
</div>
<p><a class="ef-link" href="{{ route('orders.index') }}">Ordini dei tuoi agenti ({{ $ordersCount }})</a></p>

<div class="ef-block">
<h3>I tuoi agenti</h3>
@if ($agents->isEmpty())
    <div class="ef-empty">Nessun agente assegnato a te: lo assegna il back office.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Agente</th><th>Zona</th><th>Dealer</th></tr></thead>
    <tbody>
    @foreach ($agents as $agent)
        <tr><td><strong>{{ $agent->user->name }}</strong><br><span class="ef-muted">{{ $agent->user->email }}</span></td><td>{{ $agent->region }}<br><span class="ef-muted">{{ $agent->zones }}</span></td><td>{{ $agent->dealers_count }}</td></tr>
    @endforeach
    </tbody>
</table></div>
@endif
</div>

<div class="ef-block">
<h3>Dealer</h3>
@if ($dealers->isEmpty())
    <div class="ef-empty">Ancora nessun dealer sotto i tuoi agenti.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Negozio</th><th>Zona</th><th>Agente</th><th>Anagrafica</th></tr></thead>
    <tbody>
    @foreach ($dealers as $dealer)
        <tr>
            <td><strong>{{ $dealer->business_name }}</strong><br><span class="ef-muted">{{ $dealer->contact_name }}</span></td>
            <td>{{ $dealer->region }}<br><span class="ef-muted">{{ $dealer->municipality }}</span></td>
            <td>{{ $dealer->agent?->user->name }}</td>
            <td>@if ($dealer->registryState() === 'completa')<span class="ef-badge ef-badge--ok">completa</span>@elseif ($dealer->registryState() === 'in_verifica')<span class="ef-badge">in verifica</span>@else<span class="ef-badge ef-badge--warn">da completare</span>@endif</td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
</div>
@endsection
