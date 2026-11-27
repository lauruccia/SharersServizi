@extends('emails.layout')
@section('content')
<p>Buongiorno {{ $user->name }},</p>
<p>il back office ti ha assegnato una nuova password provvisoria.</p>
<p style="padding:14px;background:#f2f3f7;border-radius:8px">
    Nome utente: <strong>{{ $user->username }}</strong><br>
    Password provvisoria: <strong>{{ $temporaryPassword }}</strong>
</p>
<p><a href="{{ route('login') }}">Accedi all'area riservata</a>: al primo accesso dovrai scegliere una password tua.</p>
@endsection
