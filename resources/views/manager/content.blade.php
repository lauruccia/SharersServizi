@extends('layouts.area')
@section('title', 'Area manager: materiali e FAQ')
@section('nav')@include('partials.manager-nav')@endsection

@section('content')
<h1>Materiali e FAQ</h1>
<p>Quello che pubblichi qui compare subito nelle aree di agenti e dealer.</p>

<div class="ef-cols2 ef-block" style="align-items:start">
    <form class="ef-card" method="post" action="{{ route('manager.content.resource.store') }}" enctype="multipart/form-data">
        @csrf
        <h3>Nuovo materiale</h3>
        <div class="ef-field">
            <label for="kind">Dove compare</label>
            <select class="ef-input" id="kind" name="kind" required>
                @foreach (\App\Models\Resource::KINDS as $key => $label)<option value="{{ $key }}" @selected(old('kind') === $key)>{{ $label }}</option>@endforeach
            </select>
        </div>
        <div class="ef-field"><label for="title">Titolo</label><input class="ef-input" id="title" type="text" name="title" value="{{ old('title') }}" maxlength="255" required></div>
        <div class="ef-field"><label for="description">Descrizione (facoltativa)</label><input class="ef-input" id="description" type="text" name="description" value="{{ old('description') }}" maxlength="500"></div>
        <div class="ef-field">
            <label for="file">File</label>
            <input class="ef-input" id="file" type="file" name="file">
            <div class="ef-muted">PDF, Word, Excel, immagini o ZIP, fino a {{ intdiv(config('effepi.upload_max_kb'), 1024) }} MB.</div>
        </div>
        <div class="ef-field"><label for="url">Oppure un link (per i video, YouTube o simili)</label><input class="ef-input" id="url" type="url" name="url" value="{{ old('url') }}" maxlength="500"></div>
        <button class="ef-btn" type="submit">Pubblica</button>
    </form>

    <div>
        <h3>Materiali pubblicati</h3>
        @forelse ($resources->groupBy('kind') as $kind => $items)
            <h4>{{ \App\Models\Resource::KINDS[$kind] ?? $kind }}</h4>
            <ul class="ef-list">
                @foreach ($items as $r)
                    <li>
                        <a class="ef-link" href="{{ $r->href() }}" target="_blank" rel="noopener">{{ $r->title }}</a>
                        <form class="ef-inline-form" method="post" action="{{ route('manager.content.resource.destroy', $r) }}">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Rimuovi</button></form>
                    </li>
                @endforeach
            </ul>
        @empty
            <div class="ef-empty">Nessun materiale pubblicato.</div>
        @endforelse
    </div>
</div>

<div class="ef-block">
    <h2>Domande frequenti</h2>
    <p>L'assistente cerca la risposta in queste domande. Le parole chiave (separate da virgola) aiutano a trovarla anche con altri termini.</p>
    <form class="ef-card" method="post" action="{{ route('manager.content.faq.store') }}" style="max-width:760px;margin-bottom:20px">
        @csrf
        <h3>Nuova domanda</h3>
        <div class="ef-field"><label for="question">Domanda</label><input class="ef-input" id="question" type="text" name="question" maxlength="255" required></div>
        <div class="ef-field"><label for="answer">Risposta</label><textarea class="ef-input" id="answer" name="answer" maxlength="5000" required></textarea></div>
        <div class="ef-field"><label for="keywords">Parole chiave (facoltative)</label><input class="ef-input" id="keywords" type="text" name="keywords" maxlength="255" placeholder="bonifico, iban, pagamento"></div>
        <button class="ef-btn" type="submit">Aggiungi</button>
    </form>

    @foreach ($faqs as $faq)
        <details class="ef-faq">
            <summary>{{ $faq->question }} @unless ($faq->active)<span class="ef-badge">nascosta</span>@endunless</summary>
            <form method="post" action="{{ route('manager.content.faq.update', $faq) }}" style="margin-top:10px;max-width:760px">
                @csrf
                <div class="ef-field"><label>Domanda</label><input class="ef-input" type="text" name="question" value="{{ $faq->question }}" maxlength="255" required></div>
                <div class="ef-field"><label>Risposta</label><textarea class="ef-input" name="answer" maxlength="5000" required>{{ $faq->answer }}</textarea></div>
                <div class="ef-field"><label>Parole chiave</label><input class="ef-input" type="text" name="keywords" value="{{ $faq->keywords }}" maxlength="255"></div>
                <div class="ef-field"><label><input type="checkbox" name="active" value="1" @checked($faq->active)> Visibile</label></div>
                <button class="ef-btn ef-btn--small" type="submit">Salva</button>
            </form>
            <form method="post" action="{{ route('manager.content.faq.destroy', $faq) }}" style="margin-top:8px">@csrf<button class="ef-btn ef-btn--small ef-btn--danger" type="submit">Elimina</button></form>
        </details>
    @endforeach
</div>
@endsection
