@extends('layouts.area')
@section('title', $exists ? 'Correggi contratto' : 'Nuovo contratto manuale')
@section('nav')@include('partials.manager-nav')@endsection

@php($v = fn (string $f, $default = null) => old($f, $row->{$f} ?? $default))
@section('content')
<h1>{{ $exists ? 'Correggi il contratto '.$row->contract_id : 'Inserisci un contratto a mano' }}</h1>
<p class="ef-muted">Qui l'admin può correggere qualsiasi dato che incide sui conteggi. Una riga corretta o inserita a mano è <strong>protetta</strong>: i prossimi import non la sovrascrivono. Ogni modifica viene registrata (chi e quando).</p>
@if ($errors->any())<div class="ef-card" style="border-color:var(--red)">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

<form class="ef-card ef-block" method="post" action="{{ $exists ? route('manager.production.row.update', $row) : route('manager.production.row.store') }}">@csrf
    <div class="ef-cols3">
        <div class="ef-field"><label for="dealer_id">Dealer</label>
            <select class="ef-input" id="dealer_id" name="dealer_id" required>
                @foreach ($dealers as $d)<option value="{{ $d->id }}" @selected((int) $v('dealer_id') === $d->id)>{{ $d->business_name }}{{ $d->external_code ? ' ('.$d->external_code.')' : '' }}</option>@endforeach
            </select></div>
        <div class="ef-field"><label for="service_raw">Servizio</label>
            <select class="ef-input" id="service_raw" name="service_raw">@foreach ($services as $k => $label)<option value="{{ $k }}" @selected($v('service_raw') === $k)>{{ $label }} ({{ $k }})</option>@endforeach</select></div>
        <div class="ef-field"><label for="signed_at">Data di stipula</label><input class="ef-input" id="signed_at" type="date" name="signed_at" value="{{ $row->signed_at instanceof \Carbon\Carbon ? $row->signed_at->toDateString() : $v('signed_at') }}" required></div>
        <div class="ef-field"><label for="status">Stato contratto</label><input class="ef-input" id="status" name="status" value="{{ $v('status') }}"><div class="ef-muted">"OK Attivazione" = attivo.</div></div>
        <div class="ef-field"><label for="discard_reason">Motivo cestinato (QCC KO, KO Solvibilità…)</label><input class="ef-input" id="discard_reason" name="discard_reason" value="{{ $v('discard_reason') }}"></div>
        <div class="ef-field"><label for="customer_name">Cliente</label><input class="ef-input" id="customer_name" name="customer_name" value="{{ $v('customer_name') }}"></div>
        <div class="ef-field"><label for="sims_sold">SIM vendute (solo SIM)</label><input class="ef-input" id="sims_sold" type="number" min="0" name="sims_sold" value="{{ $v('sims_sold', 0) }}"></div>
        <div class="ef-field"><label for="sims_portability">di cui in portabilità</label><input class="ef-input" id="sims_portability" type="number" min="0" name="sims_portability" value="{{ $v('sims_portability', 0) }}"></div>
        <div class="ef-field"><label for="offer_code">Codice offerta (Sottotipo)</label><input class="ef-input" id="offer_code" name="offer_code" value="{{ $v('offer_code') }}"><div class="ef-muted">SMOB_5G_DE_0926 = 6,95; altri = 4,95.</div></div>
        <div class="ef-field"><label for="activation_type">Tipo attivazione</label><input class="ef-input" id="activation_type" name="activation_type" value="{{ $v('activation_type') }}"></div>
        <div class="ef-field"><label for="payment_method">Modalità pagamento</label><input class="ef-input" id="payment_method" name="payment_method" value="{{ $v('payment_method') }}"><div class="ef-muted">SDD, Bollettino Postale…</div></div>
        <div class="ef-field"><label for="invoice_delivery">Spedizione fattura</label><input class="ef-input" id="invoice_delivery" name="invoice_delivery" value="{{ $v('invoice_delivery') }}"><div class="ef-muted">Email, Posta Ordinaria…</div></div>
        <div class="ef-field"><label for="fibra_mnp">Fibra con portabilità</label>
            <select class="ef-input" id="fibra_mnp" name="fibra_mnp">
                <option value="auto" @selected($row->fibra_mnp_force === null)>Automatico</option>
                <option value="si" @selected($row->fibra_mnp_force === true)>Sì, è portabilità</option>
                <option value="no" @selected($row->fibra_mnp_force === false)>No</option>
            </select></div>
        <div class="ef-field"><label for="force">Conteggio</label>
            <select class="ef-input" id="force" name="force">
                <option value="auto" @selected($row->force_count === null)>Automatico (dallo stato)</option>
                <option value="count" @selected($row->force_count === true)>Conta sempre</option>
                <option value="exclude" @selected($row->force_count === false)>Non conta</option>
            </select></div>
    </div>
    <div class="ef-field"><label for="edit_note">Motivo della correzione (resta nello storico)</label><input class="ef-input" id="edit_note" name="edit_note" value="{{ $v('edit_note') }}"></div>
    <button class="ef-btn" type="submit">{{ $exists ? 'Salva la correzione' : 'Inserisci il contratto' }}</button>
    @if ($exists)<a class="ef-btn ef-btn--ghost" href="{{ route('production.dealer', ['dealer' => $row->dealer_id, 'period' => 'month', 'month' => $row->signed_month->format('Y-m')]) }}">Annulla</a>@endif
</form>
@endsection
