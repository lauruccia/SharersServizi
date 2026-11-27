@extends('layouts.area')
@section('title', 'Produzione e compensi')
@section('nav')@include('partials.role-nav')@endsection

@php($eur = fn (int $c) => \App\Services\ProductionReport::eur($c))
@section('content')
<h1>Produzione e compensi</h1>
<p class="ef-muted">Attivazioni SIM, luce, gas e fibra con le relative portabilità (MNP). Contano i contratti in "OK Attivazione"; quelli in lavorazione sono indicati a parte. Vedi solo i dealer che stanno sotto di te.</p>

@include('partials.date-filter', ['range' => $range, 'action' => route('production.index'), 'extra' => ['group' => $by]])

<nav class="ef-tabs" aria-label="Raggruppa per" style="margin-top:20px">
    @foreach ($groups as $key => $label)
        <a class="ef-tab" href="{{ route('production.index', $range->query() + ['group' => $key]) }}" @if ($by === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>

<div class="ef-cols4 ef-block" style="margin-top:20px">
    <div class="ef-card"><div class="ef-label">Attivazioni</div><p class="ef-kpi">{{ $totals['derived']['activations'] }}</p><div class="ef-muted">di cui {{ $totals['derived']['mnp'] }} in portabilità</div></div>
    <div class="ef-card"><div class="ef-label">SIM</div><p class="ef-kpi">{{ $totals['metrics']['sim'] }}</p><div class="ef-muted">di cui {{ $totals['metrics']['sim_mnp'] }} MNP</div></div>
    <div class="ef-card"><div class="ef-label">Luce e gas · fibra</div><p class="ef-kpi">{{ $totals['derived']['luce_gas'] }} · {{ $totals['metrics']['fibra'] }}</p><div class="ef-muted">fibra con MNP: {{ $totals['metrics']['fibra_mnp'] }}</div></div>
    <div class="ef-card"><div class="ef-label">Compensi</div><p class="ef-kpi ef-kpi--good">{{ $eur($totals['earnings']['total']) }}</p><div class="ef-muted">in lavorazione: {{ $totals['derived']['pending'] }}</div></div>
</div>

@if ($rows->isEmpty())
    <div class="ef-empty ef-block">Nessun contratto con data di stipula in questo periodo.</div>
@else
<div class="ef-table-wrap ef-block"><table class="ef-table">
    <thead><tr>
        <th>{{ $groups[$by] }}</th>@if ($by !== 'dealer')<th>Dealer</th>@endif
        <th>SIM</th><th>di cui MNP</th><th>Luce</th><th>Gas</th><th>Fibra</th><th>Fibra MNP</th>
        <th>Attivazioni</th><th>di cui MNP</th><th>In lavorazione</th><th>Compensi</th>
    </tr></thead>
    <tbody>
    @foreach ($rows as $g)
        <tr>
            <td>@if ($g['dealer'])<a class="ef-link" href="{{ route('production.dealer', ['dealer' => $g['dealer']->id] + $range->query()) }}"><strong>{{ $g['label'] }}</strong></a>@else<strong>{{ $g['label'] }}</strong>@endif</td>
            @if ($by !== 'dealer')<td>{{ $g['dealers'] }}</td>@endif
            <td>{{ $g['metrics']['sim'] }}</td><td>{{ $g['metrics']['sim_mnp'] }}</td>
            <td>{{ $g['metrics']['luce'] }}</td><td>{{ $g['metrics']['gas'] }}</td>
            <td>{{ $g['metrics']['fibra'] }}</td><td>{{ $g['metrics']['fibra_mnp'] }}</td>
            <td><strong>{{ $g['derived']['activations'] }}</strong></td><td><strong>{{ $g['derived']['mnp'] }}</strong></td>
            <td>{{ $g['derived']['pending'] ?: '—' }}</td>
            <td><strong>{{ $eur($g['earnings']['total']) }}</strong></td>
        </tr>
    @endforeach
    </tbody>
    <tfoot><tr>
        <th>Totale</th>@if ($by !== 'dealer')<th>{{ $totals['dealers'] }}</th>@endif
        <th>{{ $totals['metrics']['sim'] }}</th><th>{{ $totals['metrics']['sim_mnp'] }}</th>
        <th>{{ $totals['metrics']['luce'] }}</th><th>{{ $totals['metrics']['gas'] }}</th>
        <th>{{ $totals['metrics']['fibra'] }}</th><th>{{ $totals['metrics']['fibra_mnp'] }}</th>
        <th>{{ $totals['derived']['activations'] }}</th><th>{{ $totals['derived']['mnp'] }}</th>
        <th>{{ $totals['derived']['pending'] ?: '—' }}</th><th>{{ $eur($totals['earnings']['total']) }}</th>
    </tr></tfoot>
</table></div>
<p class="ef-muted">Compensi calcolati dal piano in vigore, mese per mese, con scaglioni retroattivi. Non includono luce/gas/fibra non attivati né i contratti esclusi (QCC KO, KO solvibilità, cestinati).</p>
@endif
@endsection
