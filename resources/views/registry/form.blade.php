@extends('layouts.area')
@section('title', 'Nuova anagrafica dealer')

@php $v = fn (string $name) => old($name, $dealer->{$name}); @endphp

@section('content')
<div class="ef-form-page">
<p><a class="ef-link" href="{{ route('agent.area') }}">← Torna all'area riservata</a></p>
<h1>{{ $dealer->exists ? 'Anagrafica di '.$dealer->business_name : 'Nuova anagrafica dealer' }}</h1>
<p>Compila i dati del negozio e allega i documenti: il back office verifica e attiva il dealer. L'anagrafica viene inviata a {{ config('effepi.mail.affiliations') }}.</p>
@if ($dealer->registry_submitted_at)
    <div class="ef-alert ef-alert--warn">Anagrafica già inviata il {{ $dealer->registry_submitted_at->format('d/m/Y') }}, in attesa di verifica: puoi correggerla e inviarla di nuovo.</div>
@endif

<form class="ef-card" method="post" action="{{ route('registry.store', $dealer->exists ? $dealer : null) }}" enctype="multipart/form-data">
    @csrf
    <div class="ef-field">
        <label for="agent">Agente che inserisce l'anagrafica</label>
        <input class="ef-input ef-input--readonly" id="agent" type="text" value="{{ auth()->user()->name }}" readonly>
    </div>

    <fieldset class="ef-fieldset">
        <legend>Dati della società</legend>
        <div class="ef-field"><label for="business_name">Ragione sociale</label><input class="ef-input" id="business_name" type="text" name="business_name" value="{{ $v('business_name') }}" required></div>
        <div class="ef-cols2">
            <div class="ef-field"><label for="vat_number">Partita IVA</label><input class="ef-input" id="vat_number" type="text" name="vat_number" inputmode="numeric" maxlength="11" value="{{ $v('vat_number') }}" required></div>
            <div class="ef-field"><label for="sdi_code">Codice SDI</label><input class="ef-input" id="sdi_code" type="text" name="sdi_code" maxlength="7" value="{{ $v('sdi_code') }}" required></div>
        </div>
        <div class="ef-field"><label for="iban">IBAN</label><input class="ef-input" id="iban" type="text" name="iban" maxlength="27" value="{{ $v('iban') }}" required><div class="ef-muted">27 caratteri, senza spazi.</div></div>
        <div class="ef-field"><label for="pec">PEC</label><input class="ef-input" id="pec" type="email" name="pec" value="{{ $v('pec') }}" required></div>
        <div class="ef-field"><label for="legal_address">Sede legale</label><input class="ef-input" id="legal_address" type="text" name="legal_address" value="{{ $v('legal_address') }}" required></div>
        <div class="ef-field"><label for="operating_address">Sede operativa</label><input class="ef-input" id="operating_address" type="text" name="operating_address" value="{{ $v('operating_address') }}" required></div>
        <div class="ef-cols2">
            <div class="ef-field"><label for="email">Email</label><input class="ef-input" id="email" type="email" name="email" value="{{ $v('email') }}" required><div class="ef-muted">A questo indirizzo il dealer riceve le credenziali.</div></div>
            <div class="ef-field"><label for="legal_rep_mobile">Cellulare del legale rappresentante</label><input class="ef-input" id="legal_rep_mobile" type="tel" name="legal_rep_mobile" value="{{ $v('legal_rep_mobile') }}" required></div>
        </div>
    </fieldset>

    <fieldset class="ef-fieldset">
        <legend>Produttività mensile prevista</legend>
        <div class="ef-cols3">
            @foreach (['expected_sim' => 'SIM', 'expected_energy' => 'Energy', 'expected_fiber' => 'Fibra'] as $name => $label)
                <div class="ef-field"><label for="{{ $name }}">{{ $label }}</label><input class="ef-input" id="{{ $name }}" type="number" name="{{ $name }}" min="0" step="1" value="{{ $v($name) }}" required></div>
            @endforeach
        </div>
    </fieldset>

    <fieldset class="ef-fieldset">
        <legend>Aliquota di fatturazione</legend>
        <p class="ef-muted">Il dealer dichiara (scegli una sola opzione):</p>
        @foreach (\App\Models\Dealer::BILLING_REGIMES as $key => $text)
            <label class="ef-check"><input type="radio" name="billing_regime" value="{{ $key }}" @checked($v('billing_regime') === $key) required> <span>{{ $text }}</span></label>
        @endforeach
    </fieldset>

    <fieldset class="ef-fieldset">
        <legend>Documenti obbligatori</legend>
        <div class="ef-cols2">
            @foreach (\App\Models\DealerDocument::TYPES as $type => $label)
                <div class="ef-field">
                    <label for="doc-{{ $type }}">{{ $label }} @if ($existing->has($type))<span class="ef-badge ef-badge--ok">già caricato</span>@endif</label>
                    <input class="ef-input" id="doc-{{ $type }}" type="file" name="documents[{{ $type }}]" accept=".pdf,.jpg,.jpeg,.png" @required(! $existing->has($type))>
                </div>
            @endforeach
        </div>
        <div class="ef-muted">Formati accettati: PDF, JPG, PNG. Al massimo {{ (int) (config('effepi.upload_max_kb') / 1024) }} MB per file.</div>
    </fieldset>

    <label class="ef-check"><input type="checkbox" name="privacy" value="1" required> <span>Confermo che il dealer ha letto l'<a href="{{ route('privacy') }}" target="_blank" rel="noopener">informativa privacy</a> e acconsente al trattamento dei dati e dei documenti.</span></label>
    <button class="ef-btn" type="submit">Invia l'anagrafica</button>
</form>
</div>
@endsection
