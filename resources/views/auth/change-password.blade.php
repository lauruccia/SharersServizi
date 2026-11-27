@extends('layouts.auth')
@section('title', 'Scegli la tua password')

@section('content')
<h1>Scegli la tua password</h1>
@if (auth()->user()->must_change_password)
    <p>Stai usando la password provvisoria ricevuta via email: per entrare nell'area riservata devi sostituirla con una tua.</p>
@endif
<form method="post" action="{{ route('password.change') }}">
    @csrf
    <div class="ef-field">
        <label for="current_password">{{ auth()->user()->must_change_password ? 'Password provvisoria' : 'Password attuale' }}</label>
        <input class="ef-input" id="current_password" type="password" name="current_password" autocomplete="current-password" required>
    </div>
    <div class="ef-field">
        <label for="password">Nuova password</label>
        <input class="ef-input" id="password" type="password" name="password" autocomplete="new-password" required>
        <div class="ef-muted">Almeno 10 caratteri, con lettere e numeri.</div>
    </div>
    <div class="ef-field">
        <label for="password_confirmation">Ripeti la nuova password</label>
        <input class="ef-input" id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
    </div>
    <button class="ef-btn" type="submit">Salva la password</button>
</form>
<form method="post" action="{{ route('logout') }}" style="margin-top:20px">@csrf<button class="ef-btn ef-btn--ghost ef-btn--small" type="submit">Esci</button></form>
@endsection
