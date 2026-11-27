@extends('layouts.auth')
@section('title', 'Accedi')

@section('content')
<h1>Area riservata</h1>
<p>Per dealer e agenti approvati dal nostro back office.</p>
<form method="post" action="{{ route('login') }}">
    @csrf
    <div class="ef-field">
        <label for="login">Nome utente o email</label>
        <input class="ef-input" id="login" type="text" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus>
    </div>
    <div class="ef-field">
        <label for="password">Password</label>
        <input class="ef-input" id="password" type="password" name="password" autocomplete="current-password" required>
    </div>
    <label class="ef-check"><input type="checkbox" name="remember" value="1"> <span>Resta collegato su questo dispositivo</span></label>
    <div class="ef-actions">
        <button class="ef-btn" type="submit">Accedi</button>
        <a class="ef-link" href="{{ route('password.request') }}">Hai dimenticato la password?</a>
    </div>
</form>
<p class="ef-muted" style="margin-top:28px">Non hai un accesso? <a class="ef-link" href="{{ route('home') }}#registrati">Registrati</a></p>
@endsection
