@extends('layouts.auth')
@section('title', 'Password dimenticata')

@section('content')
<h1>Password dimenticata</h1>
<p>Scrivi l'email con cui sei registrato: ti mandiamo il link per sceglierne una nuova.</p>
<form method="post" action="{{ route('password.email') }}">
    @csrf
    <div class="ef-field">
        <label for="email">Email</label>
        <input class="ef-input" id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
    </div>
    <div class="ef-actions">
        <button class="ef-btn" type="submit">Invia il link</button>
        <a class="ef-link" href="{{ route('login') }}">Torna all'accesso</a>
    </div>
</form>
@endsection
