@extends('layouts.area')
@section('title', 'Extra gare')
@section('nav')@include('partials.role-nav')@endsection

@section('content')
<h1>Extra gare sulla portabilità</h1>
<p class="ef-muted">La classifica conta le portabilità (MNP) dei contratti attivi, con data di stipula nel periodo della gara. Vedi solo i dealer che stanno sotto di te.</p>

@if (auth()->user()->isBackOffice())
<form class="ef-card ef-block" method="post" action="{{ route('manager.contests.store') }}">@csrf
    <h3>Avvia una nuova extra gara</h3>
    <div class="ef-cols3">
        <div class="ef-field"><label for="name">Nome</label><input class="ef-input" id="name" name="name" value="{{ old('name') }}" required></div>
        <div class="ef-field"><label for="metric">Cosa si conta</label>
            <select class="ef-input" id="metric" name="metric">@foreach ($metrics as $k => $label)<option value="{{ $k }}" @selected(old('metric') === $k)>{{ $label }}</option>@endforeach</select></div>
        <div class="ef-field"><label for="prize">Premio</label><input class="ef-input" id="prize" name="prize" value="{{ old('prize') }}"></div>
        <div class="ef-field"><label for="starts_on">Dal</label><input class="ef-input" id="starts_on" type="date" name="starts_on" value="{{ old('starts_on', now()->toDateString()) }}" required></div>
        <div class="ef-field"><label for="ends_on">Al</label><input class="ef-input" id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', now()->endOfMonth()->toDateString()) }}" required></div>
        <div class="ef-field"><label for="target">Obiettivo (portabilità, facoltativo)</label><input class="ef-input" id="target" type="number" min="1" name="target" value="{{ old('target') }}"></div>
    </div>
    @if ($errors->any())<p class="ef-muted" style="color:var(--red)">{{ $errors->first() }}</p>@endif
    <button class="ef-btn" type="submit">Avvia la gara</button>
</form>
@endif

@forelse ($contests as $item)
    @php($c = $item['contest'])
    <div class="ef-card ef-block">
        <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
            <div>
                <h3 style="margin:0">{{ $c->name }} @if ($c->isRunning())<span class="ef-badge ef-badge--ok">in corso</span>@else<span class="ef-badge">conclusa</span>@endif</h3>
                <div class="ef-muted">{{ $metrics[$c->metric] }} · dal {{ $c->starts_on->format('d/m/Y') }} al {{ $c->ends_on->format('d/m/Y') }}@if ($c->prize) · Premio: {{ $c->prize }}@endif @if ($c->target) · Obiettivo: {{ $c->target }}@endif</div>
            </div>
            @if (auth()->user()->isBackOffice() && $c->isRunning())
                <form method="post" action="{{ route('manager.contests.close', $c) }}">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Chiudi la gara</button></form>
            @endif
        </div>
        @if ($item['ranking']->isEmpty())
            <div class="ef-empty" style="margin-top:12px">Ancora nessuna portabilità nel periodo.</div>
        @else
            <div class="ef-table-wrap" style="margin-top:12px"><table class="ef-table">
                <thead><tr><th>#</th><th>Dealer</th><th>Portabilità</th>@if ($c->target)<th>Obiettivo</th>@endif</tr></thead>
                <tbody>
                @foreach ($item['ranking'] as $i => $r)
                    <tr><td>{{ $i + 1 }}</td><td>{{ $r['dealer']->business_name }}</td><td><strong>{{ $r['value'] }}</strong></td>
                        @if ($c->target)<td>@if ($r['value'] >= $c->target)<span class="ef-badge ef-badge--ok">raggiunto</span>@else {{ $r['value'] }} / {{ $c->target }}@endif</td>@endif</tr>
                @endforeach
                </tbody>
            </table></div>
        @endif
    </div>
@empty
    <div class="ef-empty ef-block">Nessuna extra gara avviata.</div>
@endforelse
@endsection
