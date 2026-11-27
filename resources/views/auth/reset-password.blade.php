@extends('layouts.auth')
@section('title', 'Nuova password')

@section('content')
<h1>Scegli una nuova password</h1>
<form method="post" action="{{ route('password.update') }}">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="ef-field">
        <label for="email">Email</label>
        <input class="ef-input" id="email" type="email" name="email" value="{{ old('email', $email) }}" autocomplete="email" required>
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
@endsection
