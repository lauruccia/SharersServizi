@extends('layouts.area')
@section('title', 'Domande e risposte')

@section('content')
<div class="ef-form-page">
<p><a class="ef-link" href="{{ route(auth()->user()->homeRoute()) }}">← Torna all'area riservata</a></p>
<h1>Domande e risposte</h1>
<form class="ef-card" method="get" action="{{ route('assistant') }}">
    <label for="q">Cosa vuoi sapere?</label>
    <div class="ef-actions">
        <input class="ef-input" id="q" type="search" name="q" maxlength="200" value="{{ $q }}" required style="flex:1;min-width:220px">
        <button class="ef-btn" type="submit">Cerca la risposta</button>
    </div>
</form>

@if ($q !== '')
    <div class="ef-block">
        @if ($answers->isEmpty())
            <div class="ef-alert ef-alert--warn">Non ho trovato una risposta. <a class="ef-link" href="{{ route('tickets.create') }}">Apri una segnalazione</a>: ti risponde il back office.</div>
        @else
            @foreach ($answers as $faq)
                <div class="ef-card" style="margin-bottom:12px"><h3>{{ $faq->question }}</h3><p style="white-space:pre-line">{{ $faq->answer }}</p></div>
            @endforeach
            <p class="ef-muted">Non è quello che cercavi? <a class="ef-link" href="{{ route('tickets.create') }}">Apri una segnalazione</a>.</p>
        @endif
    </div>
@endif

<div class="ef-block">
    <h2>Tutte le domande</h2>
    @forelse ($faqs as $faq)
        <details class="ef-faq"><summary>{{ $faq->question }}</summary><p style="white-space:pre-line">{{ $faq->answer }}</p></details>
    @empty
        <div class="ef-empty">Le domande frequenti saranno pubblicate a breve.</div>
    @endforelse
</div>
</div>
@endsection
