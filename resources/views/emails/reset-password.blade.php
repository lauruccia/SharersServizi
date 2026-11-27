@extends('emails.layout')
@section('content')
<p>Buongiorno {{ $user->name }},</p>
<p>abbiamo ricevuto una richiesta di reimpostare la password del tuo accesso (nome utente: <strong>{{ $user->username }}</strong>).</p>
<p><a href="{{ route('password.reset', ['token' => $token, 'email' => $user->email]) }}" style="display:inline-block;padding:12px 22px;background:#98c23a;color:#ffffff;text-decoration:none;border-radius:30px;font-weight:bold">Scegli una nuova password</a></p>
<p>Il link vale 60 minuti. Se non sei stato tu a chiederlo, ignora questa email.</p>
@endsection
