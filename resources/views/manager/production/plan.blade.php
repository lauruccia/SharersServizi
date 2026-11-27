@extends('layouts.area')
@section('title', 'Area manager: piano compensi')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Piano compensi dealer</h1>
<p>Importi per pratica. Lo scaglione è <strong>mensile e retroattivo</strong>: raggiunta la soglia, la tariffa vale per tutte le pratiche del mese. Ogni piano vale dal mese indicato in poi: per cambiare solo i mesi futuri scegli un mese di validità successivo, i mesi passati restano con il piano che avevano.</p>

<form class="ef-card" method="get" action="{{ route('manager.plan') }}">
    <div class="ef-field" style="margin:0"><label for="m">Vedi il piano in vigore nel mese</label>
        <input class="ef-input" id="m" type="month" name="month" value="{{ $month->format('Y-m') }}" onchange="this.form.submit()"></div>
</form>

@if ($errors->any())<div class="ef-card ef-block" style="border-color:var(--red)">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form method="post" action="{{ route('manager.plan.update') }}" class="ef-block">@csrf
    <div class="ef-cols3">
        <div class="ef-field"><label for="valid_from">Valido dal mese</label><input class="ef-input" id="valid_from" type="month" name="valid_from" value="{{ old('valid_from', $month->format('Y-m')) }}" required>
            <div class="ef-muted">Se esiste già un piano con questo mese, viene corretto; altrimenti ne nasce uno nuovo.</div></div>
        <div class="ef-field"><label for="note">Nota</label><input class="ef-input" id="note" name="note" value="{{ old('note', $plan->note) }}"></div>
    </div>

    <h3>Scaglioni e importi (€ a pratica)</h3>
    <div class="ef-table-wrap"><table class="ef-table" id="rules">
        <thead><tr><th>Prodotto</th><th>Da (pratiche/mese)</th><th>A (vuoto = senza limite)</th><th>Attivazione / base</th><th>Aggiunta MNP</th></tr></thead>
        <tbody>
        @php($rules = old('rules') ?? $plan->rules->map(fn ($r) => ['product' => $r->product, 'min_qty' => $r->min_qty, 'max_qty' => $r->max_qty, 'base_amount' => $r->base_amount, 'mnp_amount' => $r->mnp_amount])->all())
        @foreach ($rules as $i => $r)
            <tr>
                <td><input type="hidden" name="rules[{{ $i }}][product]" value="{{ $r['product'] }}">{{ $products[$r['product']] }}</td>
                <td><input class="ef-input" type="number" min="1" name="rules[{{ $i }}][min_qty]" value="{{ $r['min_qty'] }}" required></td>
                <td><input class="ef-input" type="number" min="1" name="rules[{{ $i }}][max_qty]" value="{{ $r['max_qty'] }}"></td>
                <td><input class="ef-input" type="number" min="0" step="0.01" name="rules[{{ $i }}][base_amount]" value="{{ $r['base_amount'] }}" required></td>
                <td>@if (str_starts_with($r['product'], 'energy'))<span class="ef-muted">—</span><input type="hidden" name="rules[{{ $i }}][mnp_amount]" value="0">@else<input class="ef-input" type="number" min="0" step="0.01" name="rules[{{ $i }}][mnp_amount]" value="{{ $r['mnp_amount'] }}">@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
    <p class="ef-muted">SIM e fibra: compenso = attivazione per ogni pratica + aggiunta MNP per quelle in portabilità. Energy: base per consumer/business secondo lo scaglione, più i bonus qui sotto.</p>

    <h3>Bonus energy e regole di conteggio</h3>
    <div class="ef-cols3">
        <div class="ef-field"><label for="sdd_bonus">Bonus addebito SDD (€)</label><input class="ef-input" id="sdd_bonus" type="number" step="0.01" min="0" name="sdd_bonus" value="{{ old('sdd_bonus', $plan->param('sdd_bonus')) }}" required></div>
        <div class="ef-field"><label for="email_bonus">Bonus fattura via email (€)</label><input class="ef-input" id="email_bonus" type="number" step="0.01" min="0" name="email_bonus" value="{{ old('email_bonus', $plan->param('email_bonus')) }}" required></div>
        <div class="ef-field"><label for="sim_scaglione">Scaglione SIM</label>
            <select class="ef-input" id="sim_scaglione" name="sim_scaglione">
                <option value="totale" @selected(old('sim_scaglione', $plan->param('sim_scaglione')) === 'totale')>Sul totale SIM del dealer (4,95 + 6,95)</option>
                <option value="per_offerta" @selected(old('sim_scaglione', $plan->param('sim_scaglione')) === 'per_offerta')>Separato per ciascuna offerta</option>
            </select></div>
        <div class="ef-field"><label for="fibra_mnp">Fibra con portabilità si riconosce da</label>
            <select class="ef-input" id="fibra_mnp" name="fibra_mnp">
                <option value="tipo_attivazione" @selected(old('fibra_mnp', $plan->param('fibra_mnp')) === 'tipo_attivazione')>Tipo attivazione = MIGRASOLODATI</option>
                <option value="portabilita" @selected(old('fibra_mnp', $plan->param('fibra_mnp')) === 'portabilita')>Colonna nSimPortabilita &gt; 0</option>
            </select></div>
        <div class="ef-field"><label for="sim_695_codes">Codici offerta SIM a 6,95</label>
            <textarea class="ef-input" id="sim_695_codes" name="sim_695_codes" rows="2">{{ old('sim_695_codes', implode(', ', (array) $plan->param('sim_695_codes'))) }}</textarea>
            <div class="ef-muted">Separati da virgola. Ogni altro codice è a 4,95.</div></div>
        <div class="ef-field"><label><input type="checkbox" name="count_pending" value="1" @checked(old('count_pending', $plan->param('count_pending')))> Paga anche i contratti "in lavorazione"</label>
            <div class="ef-muted">Di norma no: si pagano solo gli "OK Attivazione".</div></div>
    </div>
    <button class="ef-btn" type="submit">Salva il piano</button>
</form>

<h3 class="ef-block">Storico dei piani</h3>
<div class="ef-table-wrap"><table class="ef-table">
    <thead><tr><th>Valido dal</th><th>Nota</th><th></th></tr></thead>
    <tbody>@foreach ($plans as $p)<tr><td>{{ ucfirst($p->valid_from->isoFormat('MMMM YYYY')) }}</td><td>{{ $p->note }}</td><td><a class="ef-link" href="{{ route('manager.plan', ['month' => $p->valid_from->format('Y-m')]) }}">Apri</a></td></tr>@endforeach</tbody>
</table></div>
@endsection
