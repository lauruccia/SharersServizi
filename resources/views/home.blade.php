@extends('layouts.public')
@section('title', 'Master Dealer Optima per il Centro Sud')

@php
    $regions = config('effepi.regions');
    $dealerActive = $errors->dealer->any();
    $agentActive = $errors->agent->any();
    $field = fn (string $bag, string $name) => ($bag === 'dealer' ? $dealerActive : $agentActive) ? old($name) : '';
@endphp

@section('content')
<div class="ef-banner"><div class="ef-banner__in"><strong>Miglior Master Agent d'Italia</strong><span>Premio Optima · agosto 2026</span></div></div>

<div class="ef-hero-band">
<section class="ef-wrap ef-hero">
    <div>
        <h1>Vicini ai negozi di prossimità: Effepi consiglia le scelte giuste</h1>
        <p class="ef-hero__lead">Il tuo negozio presidia il territorio, noi ti aiutiamo a scegliere il miglior competitor per luce, gas e fibra, a presidiare il web a zero pensieri e farti diventare un hub di servizi digitali.</p>
        <div class="ef-actions">
            <a class="ef-btn" href="#registrati-dealer">Registrati come dealer</a>
            <a class="ef-btn ef-btn--ghost" href="#registrati-agente">Registrati come agente</a>
        </div>
        <p class="ef-micro">Ti ricontattiamo per conoscerci e verificare la disponibilità nella tua zona. Preferisci parlarne subito? <a href="tel:+390773273136">Chiama lo 0773 273136</a></p>
    </div>
    @include('partials.home-map', ['regions' => $regions])
</section>
</div>

<section class="ef-section ef-soft">
    <div class="ef-wrap">
        <h3>La nostra rete negli ultimi 12 mesi</h3>
        <div class="ef-numbers">
            @foreach (['dealer' => 'dealer', 'sim' => 'SIM', 'luce_gas' => 'contratti luce e gas', 'fibra' => 'contratti fibra'] as $key => $label)
                <div class="ef-number">
                    <div class="ef-number__circle" data-count="{{ $numbers[$key] }}">{{ number_format($numbers[$key], 0, ',', '.') }}</div>
                    <span>{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </div>
</section>

@include('partials.home-hub')

<section class="ef-section ef-dark" id="chi-siamo">
    <div class="ef-wrap">
        <div class="ef-eyebrow">Chi siamo</div>
        <h2 style="max-width:18em">Una scommessa nata attorno a un tavolo, diventata il miglior Master Agent d'Italia</h2>
        <div class="ef-grid2" style="margin-top:32px">
            <div>
                <p>Effepi Telefonia entra nel canale retail nel 2020 con la vendita di servizi, come agenzia Fastweb per il Lazio.</p>
                <p>La svolta arriva nel dicembre 2022. Seduti al tavolo di un albergo, il direttore vendite Fabrizio Ghigo e l'area manager Elisa Bibi ci prospettano una nuova avventura con Optima Italia S.p.A.: creare una rete distributiva che permetta ai negozi di prossimità di affacciarsi alla vendita di servizi energetici integrati ai servizi telco.</p>
                <p>Partiamo in start-up da Sicilia e Lazio, con un po' di scetticismo. I risultati ci sorprendono: la visione di Optima era corretta. Scegliamo allora di indossare la maglia di Master Agent monomandatario e di investire nel progetto tutte le nostre forze e risorse.</p>
                <p>Il 28 agosto 2026, alla convention annuale, saliamo sul podio con il premio di miglior Master Agent d'Italia. È il riconoscimento che ci apre la possibilità di presidiare tutto il Centro Sud.</p>
            </div>
            <ul class="ef-timeline">
                <li><strong>2020</strong><span>Ingresso nel retail come agenzia Fastweb per il Lazio</span></li>
                <li><strong>Dicembre 2022</strong><span>Nasce il progetto con Optima, in start-up da Sicilia e Lazio</span></li>
                <li><strong>28 agosto 2026</strong><span>Premio come miglior Master Agent d'Italia</span></li>
                <li><strong>Oggi</strong><span>La possibilità di presidiare tutto il Centro Sud</span></li>
            </ul>
        </div>
    </div>
</section>

<section class="ef-section" id="per-i-negozi">
    <div class="ef-wrap">
        <h2>I nostri brand, un solo obiettivo: più clienti in negozio</h2>
        <p style="max-width:44em">Optima ti dà i servizi da vendere. Sharers ti dà il sito per farti trovare e restare in contatto con i tuoi clienti. Rapicom aggiunge i servizi digitali di pagamento e ricarica che portano i clienti in negozio.</p>
        <div class="ef-cols4 ef-brands ef-stagger" data-reveal style="margin:28px 0">
            <div class="ef-card"><h3>Optima</h3><p>Offerta completa per famiglie, micro business e condomini.</p><ul><li>Luce e gas</li><li>Fibra</li><li>SIM</li></ul></div>
            <div class="ef-card"><h3>Sharers</h3><p>Sito e-commerce chiavi in mano per presidiare il territorio online.</p><ul><li>Sito e dominio tuoi</li><li>Scegli tra 15.000 articoli</li><li>Ordini diretti a te</li><li>Clienti di zona in negozio</li></ul></div>
            <div class="ef-card"><h3>Rapicom</h3><p>Servizi digitali per esercenti: nuovi motivi per entrare in negozio.</p><ul><li>Bollettini, MAV e RAV</li><li>Bollo auto</li><li>Ricariche telefoniche</li><li>Amazon e videogame</li></ul></div>
            <div class="ef-card"><h3>Altri operatori mobile</h3><p>Altre proposte di telefonia mobile per i clienti del negozio.</p></div>
        </div>
        <p><strong>Lavoriamo con negozi di telefonia multibrand, specialisti dell'energia, CAF e agenzie immobiliari.</strong></p>
        <a class="ef-btn" href="#registrati-dealer">Registrati come dealer</a>
    </div>
</section>

@include('partials.home-chain')

<section class="ef-section ef-soft" id="diventa-agente">
    <div class="ef-wrap ef-band">
        <div>
            <h2>Cerchiamo agenti nel Centro Sud</h2>
            <p>Conosci i negozi della tua zona? Costruisci con noi la rete sul tuo territorio.</p>
            <p><strong>Aree di competenza:</strong> {{ implode(', ', array_slice($regions, 0, -1)) }} e {{ end($regions) }}.</p>
        </div>
        <a class="ef-btn" href="#registrati-agente">Registrati come agente</a>
    </div>
</section>

@include('partials.home-steps')

@include('partials.home-faq')

<section class="ef-section" id="registrati">
    <div class="ef-wrap">
        <h2>Entra nella rete Effepi</h2>
        <p style="max-width:40em">Compila il modulo adatto a te: ti ricontattiamo per conoscerci e verificare la disponibilità nella tua zona.</p>
        <div class="ef-regtabs" role="group" aria-label="Scegli il modulo">
            <button type="button" class="ef-regtab" data-regtab="dealer" aria-pressed="{{ $agentActive ? 'false' : 'true' }}">Sono un dealer</button>
            <button type="button" class="ef-regtab" data-regtab="agent" aria-pressed="{{ $agentActive ? 'true' : 'false' }}">Sono un agente</button>
        </div>
        <div class="ef-cols2 ef-regs" data-regs data-active="{{ $agentActive ? 'agent' : 'dealer' }}" style="margin-top:28px;align-items:start">

            <form class="ef-card" id="registrati-dealer" method="post" action="{{ route('registration.dealer') }}" data-booking="{{ route('videocall.slots') }}">
                @csrf
                <h3>Registrati come dealer</h3>
                @if ($dealerActive)<div class="ef-alert ef-alert--error" role="alert">Controlla i campi segnalati qui sotto.</div>@endif
                @foreach ([['business_name', 'Nome del negozio o ragione sociale', 'text', 'organization'], ['name', 'Nome e cognome del referente', 'text', 'name'], ['phone', 'Telefono', 'tel', 'tel'], ['email', 'Email', 'email', 'email']] as [$name, $label, $type, $auto])
                    <div class="ef-field">
                        <label for="dealer-{{ $name }}">{{ $label }}</label>
                        <input class="ef-input" id="dealer-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $field('dealer', $name) }}" autocomplete="{{ $auto }}" required>
                        @error($name, 'dealer')<div class="ef-error">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div class="ef-field">
                    <label for="dealer-activity">Tipo di attività</label>
                    <select class="ef-input" id="dealer-activity" name="activity_type" required>
                        <option value="">Seleziona</option>
                        @foreach (config('effepi.activity_types') as $type)<option @selected($field('dealer', 'activity_type') === $type)>{{ $type }}</option>@endforeach
                    </select>
                    @error('activity_type', 'dealer')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="ef-field">
                    <label for="dealer-region">Regione</label>
                    <select class="ef-input" id="dealer-region" name="region" required>
                        <option value="">Seleziona</option>
                        @foreach ($regions as $region)<option @selected($field('dealer', 'region') === $region)>{{ $region }}</option>@endforeach
                    </select>
                    @error('region', 'dealer')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="ef-field">
                    <label for="dealer-municipality">Comune del negozio</label>
                    <input class="ef-input" id="dealer-municipality" type="text" name="municipality" value="{{ $field('dealer', 'municipality') }}" required>
                    @error('municipality', 'dealer')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                @include('partials.booking', ['bag' => 'dealer', 'active' => $dealerActive])
            </form>

            <form class="ef-card" id="registrati-agente" method="post" action="{{ route('registration.agent') }}" data-booking="{{ route('videocall.slots') }}">
                @csrf
                <h3>Registrati come agente</h3>
                @if ($agentActive)<div class="ef-alert ef-alert--error" role="alert">Controlla i campi segnalati qui sotto.</div>@endif
                @foreach ([['name', 'Nome e cognome', 'text', 'name'], ['phone', 'Telefono', 'tel', 'tel'], ['email', 'Email', 'email', 'email']] as [$name, $label, $type, $auto])
                    <div class="ef-field">
                        <label for="agent-{{ $name }}">{{ $label }}</label>
                        <input class="ef-input" id="agent-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ $field('agent', $name) }}" autocomplete="{{ $auto }}" required>
                        @error($name, 'agent')<div class="ef-error">{{ $message }}</div>@enderror
                    </div>
                @endforeach
                <div class="ef-field">
                    <label for="agent-region">Regione di interesse</label>
                    <select class="ef-input" id="agent-region" name="region" required>
                        <option value="">Seleziona</option>
                        @foreach ($regions as $region)<option @selected($field('agent', 'region') === $region)>{{ $region }}</option>@endforeach
                    </select>
                    @error('region', 'agent')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="ef-field">
                    <label for="agent-zones">Province o zone che conosci meglio</label>
                    <input class="ef-input" id="agent-zones" type="text" name="zones" value="{{ $field('agent', 'zones') }}" required>
                    @error('zones', 'agent')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                <div class="ef-field">
                    <label for="agent-experience">La tua esperienza</label>
                    <textarea class="ef-input" id="agent-experience" name="experience" placeholder="Settori, aziende e rapporti con i negozi">{{ $field('agent', 'experience') }}</textarea>
                    @error('experience', 'agent')<div class="ef-error">{{ $message }}</div>@enderror
                </div>
                @include('partials.booking', ['bag' => 'agent', 'active' => $agentActive])
            </form>
        </div>
    </div>
</section>

<section class="ef-section ef-soft" id="contatti">
    <div class="ef-wrap">
        <h2>Preferisci parlarci direttamente?</h2>
        <p>Chiamaci o scrivici.</p>
        <div class="ef-actions">
            <a class="ef-btn" href="tel:+390773273136">Chiama 0773 273136</a>
            <a class="ef-btn ef-btn--ghost" href="tel:+393770467987">Mobile 377 0467987</a>
            <a class="ef-btn ef-btn--ghost" href="mailto:{{ config('effepi.mail.info') }}">Scrivi una email</a>
        </div>
        <p class="ef-muted" style="margin-top:18px">Sede: Viale P.L. Nervi 164, 04100 Latina</p>
    </div>
</section>

<div class="ef-sticky-cta" data-sticky-cta>
    <a class="ef-btn" href="#registrati">Registrati</a>
    <a class="ef-btn ef-btn--ghost ef-sticky-cta__call" href="tel:+390773273136" aria-label="Chiama Effepi">Chiama</a>
</div>
@endsection
