@extends('layouts.public')
@section('title', 'Informativa privacy')

@section('content')
<section class="ef-section">
    <div class="ef-wrap ef-narrow">
        <h1>Informativa privacy</h1>
        <div class="ef-alert ef-alert--warn">Testo in preparazione: l'informativa definitiva verrà pubblicata prima della messa online.</div>
        <p>Titolare del trattamento: Effepi Telefonia Srls, Viale P.L. Nervi 164, 04100 Latina.</p>
        <p>Per qualsiasi richiesta sui tuoi dati scrivi a <a href="mailto:{{ config('effepi.mail.info') }}">{{ config('effepi.mail.info') }}</a>.</p>
    </div>
</section>
@endsection
