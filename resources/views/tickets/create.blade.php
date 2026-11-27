@extends('layouts.area')
@section('title', 'Nuova segnalazione')

@section('content')
<div class="ef-form-page">
<p><a class="ef-link" href="{{ route('tickets.index') }}">← Le tue segnalazioni</a></p>
<h1>Nuova segnalazione</h1>
<p>Scrivi al back office: ti risponde qui e via email. Prima, prova a cercare la risposta nelle <a class="ef-link" href="{{ route('assistant') }}">domande frequenti</a>.</p>
<form class="ef-card" method="post" action="{{ route('tickets.store') }}">
    @csrf
    @if (auth()->user()->role === 'agent')
        <div class="ef-field">
            <label for="dealer_id">Riguarda un tuo dealer? (facoltativo)</label>
            <select class="ef-input" id="dealer_id" name="dealer_id">
                <option value="">Nessuno in particolare</option>
                @foreach ($dealers as $d)<option value="{{ $d->id }}" @selected((int) old('dealer_id') === $d->id)>{{ $d->business_name }}</option>@endforeach
            </select>
        </div>
    @endif
    <div class="ef-field">
        <label for="category">Di cosa si tratta</label>
        <select class="ef-input" id="category" name="category" required>
            @foreach (config('effepi.ticket_categories') as $key => $label)<option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div class="ef-field">
        <label for="subject">Oggetto</label>
        <input class="ef-input" id="subject" type="text" name="subject" maxlength="150" value="{{ old('subject') }}" required>
    </div>
    <div class="ef-field">
        <label for="body">Messaggio</label>
        <textarea class="ef-input" id="body" name="body" maxlength="5000" required>{{ old('body') }}</textarea>
    </div>
    <button class="ef-btn" type="submit">Invia la segnalazione</button>
</form>
</div>
@endsection
