@extends('layouts.area')
@section('title', 'Area manager: elenchi')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Elenchi della rete</h1>
<p class="ef-muted">Inserisci e gestisci dealer, aree, ambassador, agenti e capoarea. Chi ha un accesso (agenti, capoarea) riceve la password provvisoria via email.</p>

<nav class="ef-tabs" aria-label="Elenchi">
    @foreach ($tabs as $key => $label)<a class="ef-tab" href="{{ route('manager.directory', $key) }}" @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>@endforeach
</nav>
@if ($errors->any())<div class="ef-card ef-block" style="border-color:var(--red)">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

@if ($tab === 'dealer')
    <form class="ef-card ef-block" method="post" action="{{ route('manager.directory.dealer.store') }}">@csrf
        <h3>Nuovo dealer</h3>
        @include('manager.partials.dealer-fields', ['dealer' => null])
        <button class="ef-btn" type="submit">Inserisci dealer</button>
    </form>
    <form method="get" class="ef-block"><div class="ef-actions"><input class="ef-input" name="q" value="{{ $q }}" placeholder="Cerca per nome o codice"><button class="ef-btn ef-btn--ghost" type="submit">Cerca</button></div></form>
    <div class="ef-table-wrap ef-block"><table class="ef-table">
        <thead><tr><th>Dealer</th><th>Codice report</th><th>Area · ambassador</th><th>Agente</th><th></th></tr></thead>
        <tbody>
        @foreach ($items as $dealer)
            <tr><td><strong>{{ $dealer->business_name }}</strong><br><span class="ef-muted">{{ $dealer->municipality }} {{ $dealer->region }}</span></td>
                <td>{{ $dealer->external_code }}</td>
                <td>{{ $dealer->area?->name }}<br><span class="ef-muted">{{ $dealer->ambassador?->name }}</span></td>
                <td>{{ $dealer->agent?->user->name }}</td>
                <td><details class="ef-details"><summary>Modifica</summary>
                    <form method="post" action="{{ route('manager.directory.dealer.update', $dealer) }}">@csrf
                        @include('manager.partials.dealer-fields', ['dealer' => $dealer])
                        <button class="ef-btn ef-btn--small" type="submit">Salva</button>
                    </form>
                    <form method="post" action="{{ route('manager.directory.dealer.destroy', $dealer) }}" style="margin-top:8px" onsubmit="return confirm('Eliminare questo dealer?')">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Elimina</button></form>
                </details></td></tr>
        @endforeach
        </tbody>
    </table></div>
    {{ $items->links() }}

@elseif ($tab === 'aree')
    <form class="ef-card ef-block" method="post" action="{{ route('manager.directory.area.store') }}">@csrf
        <h3>Nuova area</h3>
        <div class="ef-field"><label for="name">Nome area</label><input class="ef-input" id="name" name="name" required></div>
        <button class="ef-btn" type="submit">Crea area</button>
    </form>
    <div class="ef-table-wrap ef-block"><table class="ef-table">
        <thead><tr><th>Area</th><th>Dealer</th><th>Ambassador</th><th></th></tr></thead>
        <tbody>
        @foreach ($items as $area)
            <tr><td><form method="post" action="{{ route('manager.directory.area.update', $area) }}" class="ef-actions">@csrf<input class="ef-input" name="name" value="{{ $area->name }}" required><button class="ef-btn ef-btn--small ef-btn--ghost" type="submit">Rinomina</button></form></td>
                <td>{{ $area->dealers_count }}</td><td>{{ $area->ambassadors_count }}</td>
                <td><form method="post" action="{{ route('manager.directory.area.destroy', $area) }}" onsubmit="return confirm('Eliminare l\'area? I dealer restano senza area.')">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Elimina</button></form></td></tr>
        @endforeach
        </tbody>
    </table></div>

@elseif ($tab === 'ambassador')
    <form class="ef-card ef-block" method="post" action="{{ route('manager.directory.ambassador.store') }}">@csrf
        <h3>Nuovo ambassador</h3>
        <div class="ef-cols2">
            <div class="ef-field"><label for="name">Nome</label><input class="ef-input" id="name" name="name" required></div>
            <div class="ef-field"><label for="area_id">Area</label><select class="ef-input" id="area_id" name="area_id"><option value="">—</option>@foreach ($areas as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select></div>
        </div>
        <button class="ef-btn" type="submit">Crea ambassador</button>
    </form>
    <div class="ef-table-wrap ef-block"><table class="ef-table">
        <thead><tr><th>Ambassador</th><th>Area</th><th>Dealer</th><th></th></tr></thead>
        <tbody>
        @foreach ($items as $amb)
            <tr><td colspan="2"><form method="post" action="{{ route('manager.directory.ambassador.update', $amb) }}" class="ef-actions">@csrf
                <input class="ef-input" name="name" value="{{ $amb->name }}" required>
                <select class="ef-input" name="area_id"><option value="">—</option>@foreach ($areas as $a)<option value="{{ $a->id }}" @selected($amb->area_id === $a->id)>{{ $a->name }}</option>@endforeach</select>
                <button class="ef-btn ef-btn--small ef-btn--ghost" type="submit">Salva</button></form></td>
                <td>{{ $amb->dealers_count }}</td>
                <td><form method="post" action="{{ route('manager.directory.ambassador.destroy', $amb) }}" onsubmit="return confirm('Eliminare l\'ambassador?')">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Elimina</button></form></td></tr>
        @endforeach
        </tbody>
    </table></div>

@elseif ($tab === 'agenti')
    <form class="ef-card ef-block" method="post" action="{{ route('manager.directory.agent.store') }}">@csrf
        <h3>Nuovo agente</h3>
        <div class="ef-cols3">
            <div class="ef-field"><label for="name">Nome e cognome</label><input class="ef-input" id="name" name="name" required></div>
            <div class="ef-field"><label for="email">Email</label><input class="ef-input" id="email" type="email" name="email" required></div>
            <div class="ef-field"><label for="phone">Telefono</label><input class="ef-input" id="phone" name="phone"></div>
            <div class="ef-field"><label for="region">Regione</label><input class="ef-input" id="region" name="region"></div>
            <div class="ef-field"><label for="area_head_id">Capoarea</label><select class="ef-input" id="area_head_id" name="area_head_id"><option value="">—</option>@foreach ($areaHeads as $h)<option value="{{ $h->id }}">{{ $h->user->name }}</option>@endforeach</select></div>
        </div>
        <button class="ef-btn" type="submit">Crea agente e invia credenziali</button>
    </form>
    <div class="ef-table-wrap ef-block"><table class="ef-table">
        <thead><tr><th>Agente</th><th>Capoarea</th><th>Dealer</th><th></th></tr></thead>
        <tbody>
        @foreach ($items as $agent)
            <tr><td><strong>{{ $agent->user->name }}</strong><br><span class="ef-muted">{{ $agent->user->email }}</span></td><td>{{ $agent->areaHead?->user->name }}</td><td>{{ $agent->dealers_count }}</td>
                <td><details class="ef-details"><summary>Modifica</summary>
                    <form method="post" action="{{ route('manager.directory.agent.update', $agent) }}">@csrf
                        <div class="ef-field"><label>Nome</label><input class="ef-input" name="name" value="{{ $agent->user->name }}" required></div>
                        <div class="ef-field"><label>Email</label><input class="ef-input" type="email" name="email" value="{{ $agent->user->email }}" required></div>
                        <div class="ef-field"><label>Telefono</label><input class="ef-input" name="phone" value="{{ $agent->phone }}"></div>
                        <div class="ef-field"><label>Regione</label><input class="ef-input" name="region" value="{{ $agent->region }}"></div>
                        <div class="ef-field"><label>Capoarea</label><select class="ef-input" name="area_head_id"><option value="">—</option>@foreach ($areaHeads as $h)<option value="{{ $h->id }}" @selected($agent->area_head_id === $h->id)>{{ $h->user->name }}</option>@endforeach</select></div>
                        <button class="ef-btn ef-btn--small" type="submit">Salva</button>
                    </form>
                    <p class="ef-muted">Password e sospensione dell'accesso: pagina <a class="ef-link" href="{{ route('manager.network') }}">Rete</a>.</p>
                </details></td></tr>
        @endforeach
        </tbody>
    </table></div>

@else
    <form class="ef-card ef-block" method="post" action="{{ route('manager.network.staff') }}">@csrf
        <h3>Nuovo capoarea</h3>
        <input type="hidden" name="role" value="capoarea">
        <div class="ef-cols2">
            <div class="ef-field"><label for="name">Nome e cognome</label><input class="ef-input" id="name" name="name" required></div>
            <div class="ef-field"><label for="email">Email</label><input class="ef-input" id="email" type="email" name="email" required></div>
        </div>
        <button class="ef-btn" type="submit">Crea capoarea e invia credenziali</button>
    </form>
    <div class="ef-table-wrap ef-block"><table class="ef-table">
        <thead><tr><th>Capoarea</th><th>Agenti</th><th></th></tr></thead>
        <tbody>
        @foreach ($items as $head)
            <tr><td><strong>{{ $head->user->name }}</strong><br><span class="ef-muted">{{ $head->user->email }}</span></td><td>{{ $head->agents_count }}</td>
                <td><details class="ef-details"><summary>Modifica</summary>
                    <form method="post" action="{{ route('manager.directory.areahead.update', $head) }}">@csrf
                        <div class="ef-field"><label>Nome</label><input class="ef-input" name="name" value="{{ $head->user->name }}" required></div>
                        <div class="ef-field"><label>Email</label><input class="ef-input" type="email" name="email" value="{{ $head->user->email }}" required></div>
                        <div class="ef-field"><label>Telefono</label><input class="ef-input" name="phone" value="{{ $head->phone }}"></div>
                        <div class="ef-field"><label>Regione</label><input class="ef-input" name="region" value="{{ $head->region }}"></div>
                        <button class="ef-btn ef-btn--small" type="submit">Salva</button>
                    </form>
                    <p class="ef-muted">Gli agenti si assegnano dalla scheda Agenti o dalla pagina <a class="ef-link" href="{{ route('manager.network') }}">Rete</a>.</p>
                </details></td></tr>
        @endforeach
        </tbody>
    </table></div>
@endif
@endsection
