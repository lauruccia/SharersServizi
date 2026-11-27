@extends('layouts.area')
@section('title', 'Area manager: rete')
@section('nav')@include('partials.manager-nav')
<div class="ef-block">
<h3>Capoarea ({{ $areaHeads->count() }})</h3>
@if ($areaHeads->isEmpty())
    <div class="ef-empty">Ancora nessun capoarea.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Capoarea</th><th>Accesso</th><th>Agenti</th><th></th></tr></thead>
    <tbody>
    @foreach ($areaHeads as $head)
        <tr><td><strong>{{ $head->user->name }}</strong><br><span class="ef-muted">{{ $head->user->email }}</span></td><td>@include('partials.user-state', ['user' => $head->user])</td><td>{{ $head->agents_count }}</td><td>@include('partials.user-actions', ['user' => $head->user])</td></tr>
    @endforeach
    </tbody>
</table></div>
@endif
</div>

<div class="ef-block">
<h3>Accessi interni</h3>
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Nome</th><th>Ruolo</th><th>Accesso</th><th></th></tr></thead>
    <tbody>
    @foreach ($staff as $member)
        <tr><td><strong>{{ $member->name }}</strong><br><span class="ef-muted">{{ $member->email }}</span></td><td>{{ $member->roleLabel() }}</td><td>@include('partials.user-state', ['user' => $member])</td><td>@include('partials.user-actions', ['user' => $member])</td></tr>
    @endforeach
    </tbody>
</table></div>

@if ($creatableRoles->isNotEmpty())
<form class="ef-actions" method="post" action="{{ route('manager.network.staff') }}" style="margin-top:14px">@csrf
    <select class="ef-input" name="role" aria-label="Ruolo" style="width:auto">@foreach ($creatableRoles as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
    <input class="ef-input" name="name" placeholder="Nome e cognome" aria-label="Nome e cognome" required style="width:auto">
    <input class="ef-input" type="email" name="email" placeholder="Email" aria-label="Email" required style="width:auto">
    <button class="ef-btn ef-btn--small" type="submit">Crea accesso e invia password provvisoria</button>
</form>
@endif
</div>
@endsection

@section('content')
<h1>Rete</h1>
<p>Dealer e agenti con accesso al portale. Ogni dealer va assegnato a un agente, che ne vedrà l'andamento e ne completerà l'anagrafica.</p>

<div class="ef-block">
<h3>Dealer ({{ $dealers->count() }})</h3>
@if ($dealers->isEmpty())
    <div class="ef-empty">Ancora nessun dealer approvato.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Negozio</th><th>Zona</th><th>Accesso</th><th>Anagrafica</th><th>Agente di riferimento</th><th></th></tr></thead>
    <tbody>
    @foreach ($dealers as $dealer)
        <tr>
            <td><strong>{{ $dealer->business_name }}</strong><br><span class="ef-muted">{{ $dealer->contact_name }}</span></td>
            <td>{{ $dealer->region }}<br><span class="ef-muted">{{ $dealer->municipality }}</span></td>
            <td>@include('partials.user-state', ['user' => $dealer->user])</td>
            <td>@if ($dealer->registryState() === 'completa')<span class="ef-badge ef-badge--ok">completa</span>@elseif ($dealer->registryState() === 'in_verifica')<a class="ef-badge" href="{{ route('manager.registries') }}">da verificare</a>@else<span class="ef-badge ef-badge--warn">da completare</span>@endif</td>
            <td>
                <form class="ef-actions" method="post" action="{{ route('manager.network.assign', $dealer) }}">@csrf
                    <select class="ef-input" name="agent_id" aria-label="Agente di {{ $dealer->business_name }}" style="width:auto;min-width:170px">
                        <option value="">Nessuno</option>
                        @foreach ($agents as $agent)<option value="{{ $agent->id }}" @selected($dealer->agent_id === $agent->id)>{{ $agent->user->name }}</option>@endforeach
                    </select>
                    <button class="ef-btn ef-btn--small ef-btn--ghost" type="submit">Salva</button>
                </form>
            </td>
            <td>@include('partials.user-actions', ['user' => $dealer->user])</td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
</div>

<div class="ef-block">
<h3>Agenti ({{ $agents->count() }})</h3>
@if ($agents->isEmpty())
    <div class="ef-empty">Ancora nessun agente approvato.</div>
@else
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Agente</th><th>Zona</th><th>Accesso</th><th>Dealer in portafoglio</th><th>Capoarea</th><th></th></tr></thead>
    <tbody>
    @foreach ($agents as $agent)
        <tr>
            <td><strong>{{ $agent->user->name }}</strong><br><span class="ef-muted">{{ $agent->user->email }}</span></td>
            <td>{{ $agent->region }}<br><span class="ef-muted">{{ $agent->zones }}</span></td>
            <td>@include('partials.user-state', ['user' => $agent->user])</td>
            <td>{{ $agent->dealers_count }}</td>
            <td>
                <form class="ef-actions" method="post" action="{{ route('manager.network.area-head', $agent) }}">@csrf
                    <select class="ef-input" name="area_head_id" aria-label="Capoarea di {{ $agent->user->name }}" style="width:auto;min-width:150px">
                        <option value="">Nessuno</option>
                        @foreach ($areaHeads as $head)<option value="{{ $head->id }}" @selected($agent->area_head_id === $head->id)>{{ $head->user->name }}</option>@endforeach
                    </select>
                    <button class="ef-btn ef-btn--small ef-btn--ghost" type="submit">Salva</button>
                </form>
            </td>
            <td>@include('partials.user-actions', ['user' => $agent->user])</td>
        </tr>
    @endforeach
    </tbody>
</table></div>
@endif
</div>
@endsection
