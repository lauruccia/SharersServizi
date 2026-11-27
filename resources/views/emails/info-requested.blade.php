@extends('emails.layout')
@section('content')
<p>Buongiorno {{ $registration->name }},</p>
<p>per completare la valutazione della tua richiesta ci serve qualche informazione in più:</p>
<p style="padding:14px;background:#f2f3f7;border-radius:8px">{!! nl2br(e($body)) !!}</p>
<p>Puoi rispondere direttamente a questa email.</p>
@endsection
