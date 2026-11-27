@extends('layouts.area')
@section('title', 'Area manager: Promo Dedicate')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Promo Dedicate</h1>
<p>Le offerte pubblicate qui compaiono subito nella sezione "Promo Dedicate" di agenti e dealer. Alla scadenza spariscono da sole.</p>

<div class="ef-cols2 ef-block" style="align-items:start" data-server-now="{{ now()->toIso8601String() }}">
    <form class="ef-card" method="post" action="{{ route('manager.promos.store') }}" enctype="multipart/form-data">
        @csrf
        <h3>Nuova offerta</h3>
        <div class="ef-field">
            <label for="photo">Foto del prodotto</label>
            <input class="ef-input" id="photo" type="file" name="photo" accept="image/jpeg,image/png">
            <div class="ef-muted">JPG o PNG, meglio quadrata e su sfondo chiaro.</div>
        </div>
        <div class="ef-field">
            <label for="name">Nome del prodotto</label>
            <input class="ef-input" id="name" type="text" name="name" value="{{ old('name') }}" required>
        </div>
        <div class="ef-field">
            <label for="price">Prezzo (€, IVA compresa)</label>
            <input class="ef-input" id="price" type="number" name="price" step="0.01" min="0.01" value="{{ old('price') }}" required>
        </div>
        <div class="ef-field">
            <label for="duration_hours">Durata dell'offerta</label>
            <select class="ef-input" id="duration_hours" name="duration_hours" required>
                @foreach (config('effepi.promo_durations') as $hours => $label)<option value="{{ $hours }}" @selected((int) old('duration_hours', 24) === $hours)>{{ $label }}</option>@endforeach
            </select>
            <div class="ef-muted">Il conto alla rovescia parte nel momento in cui pubblichi.</div>
        </div>
        <button class="ef-btn" type="submit">Pubblica l'offerta</button>
    </form>

    <div>
        <h3>Offerte pubblicate</h3>
        @if ($active->isEmpty())
            <div class="ef-empty">Nessuna promo pubblicata.</div>
        @else
            <div class="ef-cols2">
                @foreach ($active as $promo)
                    @component('partials.promo-card', ['promo' => $promo])
                        <form method="post" action="{{ route('manager.promos.close', $promo) }}">@csrf
                            <button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Chiudi ora</button>
                        </form>
                        <div class="ef-muted" style="margin-top:6px">Fino a {{ $promo->ends_at->isoFormat('ddd D MMM, HH:mm') }}</div>
                    @endcomponent
                @endforeach
            </div>
        @endif
        @if ($expired->isNotEmpty())
            <details class="ef-details ef-block"><summary>Ultime offerte scadute</summary>
                <ul>@foreach ($expired as $promo)<li>{{ $promo->name }} · {{ number_format($promo->price, 2, ',', '.') }} € · scaduta il {{ $promo->ends_at->format('d/m/Y H:i') }}</li>@endforeach</ul>
            </details>
        @endif
    </div>
</div>

<form class="ef-card ef-block" method="post" action="{{ route('manager.showcase.save') }}" enctype="multipart/form-data">
    @csrf
    <h3>Dal catalogo Sharers</h3>
    <p>I tre prodotti che agenti e dealer vedono sotto le Promo Dedicate: il pulsante "Vedi su sharers.it" apre il link che inserisci qui.</p>
    @foreach ($showcase as $position => $product)
        <div class="ef-cols3">
            <div class="ef-field">
                <label for="sp-name-{{ $position }}">Nome del prodotto {{ $position }}</label>
                <input class="ef-input" id="sp-name-{{ $position }}" type="text" name="products[{{ $position }}][name]" value="{{ old("products.$position.name", $product->name) }}">
            </div>
            <div class="ef-field">
                <label for="sp-url-{{ $position }}">Link su sharers.it</label>
                <input class="ef-input" id="sp-url-{{ $position }}" type="url" name="products[{{ $position }}][url]" placeholder="https://www.sharers.it/…" value="{{ old("products.$position.url", $product->url) }}">
            </div>
            <div class="ef-field">
                <label for="sp-photo-{{ $position }}">Foto @if ($product->photo_path)<span class="ef-badge ef-badge--ok">caricata</span>@endif</label>
                <input class="ef-input" id="sp-photo-{{ $position }}" type="file" name="products[{{ $position }}][photo]" accept="image/jpeg,image/png">
            </div>
        </div>
    @endforeach
    <button class="ef-btn" type="submit">Salva i prodotti</button>
</form>
@endsection
