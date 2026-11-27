@extends('layouts.area')
@section('title', 'Segnalazione #'.$ticket->id)
@section('nav')
@if (auth()->user()->isBackOffice())@include('partials.manager-nav')@endif
@endsection

@section('content')
<p><a class="ef-link" href="{{ auth()->user()->isBackOffice() ? route('manager.tickets') : route('tickets.index') }}">← Tutte le segnalazioni</a></p>
<h1>{{ $ticket->subject }}</h1>
<p class="ef-muted">
    {{ $ticket->categoryLabel() }} · aperta da {{ $ticket->user->name }}@if ($ticket->dealer) · dealer {{ $ticket->dealer->business_name }}@endif
    · <span class="ef-badge">{{ $ticket->statusLabel() }}</span>
</p>

<div class="ef-thread">
    @foreach ($ticket->messages as $m)
        <div class="ef-msg {{ $m->is_staff ? 'ef-msg--staff' : '' }}">
            <div class="ef-msg__meta">{{ $m->is_staff ? 'Back office' : ($m->user?->name ?? 'Utente') }} · {{ $m->created_at->format('d/m/Y H:i') }}</div>
            <p class="ef-msg__body">{{ $m->body }}</p>
        </div>
    @endforeach
</div>

<form class="ef-card" method="post" action="{{ route('tickets.reply', $ticket) }}" style="max-width:640px">
    @csrf
    <div class="ef-field">
        <label for="body">{{ $ticket->isClosed() ? 'Scrivi per riaprirla' : 'Rispondi' }}</label>
        <textarea class="ef-input" id="body" name="body" maxlength="5000" required></textarea>
    </div>
    <button class="ef-btn" type="submit">Invia</button>
</form>
@unless ($ticket->isClosed())
    <form method="post" action="{{ route('tickets.close', $ticket) }}" style="margin-top:12px">@csrf
        <button class="ef-btn ef-btn--ghost ef-btn--small" type="submit">Chiudi la segnalazione</button>
    </form>
@endunless
@endsection
