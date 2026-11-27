@extends('emails.layout')
@section('content')
<p>Buongiorno {{ $user->name }},</p>
<p>benvenuto nella rete Effepi: la tua richiesta come <strong>{{ $user->role === 'dealer' ? 'dealer' : 'agente' }}</strong> è stata approvata.</p>
<p>Ecco le tue credenziali per l'area riservata:</p>
<p style="padding:14px;background:#f2f3f7;border-radius:8px">
    Nome utente: <strong>{{ $user->username }}</strong><br>
    Password provvisoria: <strong>{{ $temporaryPassword }}</strong>
</p>
<p><a href="{{ route('login') }}" style="display:inline-block;padding:12px 22px;background:#98c23a;color:#ffffff;text-decoration:none;border-radius:30px;font-weight:bold">Accedi all'area riservata</a></p>
<p>Al primo accesso ti chiederemo di scegliere una password tua: quella provvisoria non sarà più valida.</p>
<p>Per qualsiasi problema scrivi a <a href="mailto:{{ config('effepi.mail.backoffice') }}">{{ config('effepi.mail.backoffice') }}</a>.</p>
@endsection
