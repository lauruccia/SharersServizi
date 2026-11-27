@extends('emails.layout')
@section('content')
<p>Nuova anagrafica dealer inviata da <strong>{{ $dealer->agent?->user?->name ?? '—' }}</strong>.</p>
<p><strong>{{ $dealer->business_name }}</strong><br>
Partita IVA {{ $dealer->vat_number }} · SDI {{ $dealer->sdi_code }}<br>
Sede operativa: {{ $dealer->operating_address }}<br>
{{ $dealer->email }} · {{ $dealer->legal_rep_mobile }}</p>
<p>Per motivi di riservatezza IBAN e documenti non sono allegati: si consultano nell'area manager.</p>
<p><a href="{{ route('manager.registries') }}" style="display:inline-block;padding:12px 22px;background:#98c23a;color:#ffffff;text-decoration:none;border-radius:30px;font-weight:bold">Apri le anagrafiche</a></p>
@endsection
